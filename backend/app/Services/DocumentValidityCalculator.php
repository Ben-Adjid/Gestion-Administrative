<?php

namespace App\Services;

use App\Models\DocumentValidityRule;
use Illuminate\Support\Carbon;

/**
 * Calcul unique de la validité d'un document (aucune logique dupliquée ailleurs).
 *
 * Règles de sécurité :
 *  - aucune durée n'est inventée : sans règle sourcée, le calcul est indisponible ;
 *  - une date de délivrance impossible (future, avant 1900, invalide) est refusée ;
 *  - la recommandation de renouvellement n'est donnée que si la règle la permet.
 */
class DocumentValidityCalculator
{
    public const STATE_VALID = 'valid';
    public const STATE_EXPIRING = 'expiring';
    public const STATE_EXPIRED = 'expired';
    public const STATE_UNAVAILABLE = 'unavailable';
    public const STATE_PERMANENT = 'permanent';
    public const STATE_STATUS_BASED = 'status_based';

    /** Délai à partir duquel un document est signalé « bientôt expiré ». */
    public const EXPIRING_DAYS = 90;

    /**
     * @return array{state:string, issue_date:?string, expiry_date:?string, days_left:?int, can_compute:bool, message:string}
     */
    public function evaluate(DocumentValidityRule $rule, ?string $issueDate, ?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();

        // Validité permanente : aucune date d'expiration, jamais de fausse échéance.
        if ($rule->status === 'permanent') {
            return $this->result(self::STATE_PERMANENT, $issueDate, null, null, false,
                'Ce document a une validité permanente : il n’a pas de date d’expiration. Certaines administrations peuvent exiger une copie récente, selon la démarche.');
        }

        // Validité liée à un statut (ex. mariage) : pas d'expiration automatique ; le statut matrimonial prime.
        if ($rule->status === 'status_based') {
            return $this->result(self::STATE_STATUS_BASED, $issueDate, null, null, false,
                'Cet acte est valable tant que le mariage subsiste, sans date d’expiration. Après un divorce légalement prononcé, il n’est plus valable : présentez le jugement de divorce si une administration le demande.');
        }

        if (! $rule->canComputeExpiry()) {
            return $this->result(self::STATE_UNAVAILABLE, $issueDate, null, null, false,
                'Le calcul de la date d’expiration n’est pas disponible : la durée de ce document doit être confirmée par une source officielle.');
        }

        $issued = $this->parseIssueDate($issueDate, $today);
        if ($issued === null) {
            return $this->result(self::STATE_UNAVAILABLE, $issueDate, null, null, false,
                'Indiquez une date de délivrance valide, qui ne soit ni dans le futur ni antérieure à 1900.');
        }

        $expiry = $rule->expiryFor($issued)->startOfDay();
        $daysLeft = (int) $today->diffInDays($expiry, false);

        if ($daysLeft < 0) {
            $state = self::STATE_EXPIRED;
            // Le casier judiciaire a sa formulation propre : sa validité est une durée de référence, pas une expiration légale.
            $message = $rule->key === 'casier-judiciaire'
                ? 'Validité dépassée — renouvellement à vérifier. L’administration destinataire peut imposer des conditions particulières d’ancienneté.'
                : 'Ce document est expiré depuis ' . abs($daysLeft) . ' jour(s). Un renouvellement est recommandé.';
        } elseif ($daysLeft <= self::EXPIRING_DAYS) {
            $state = self::STATE_EXPIRING;
            $message = 'Ce document expire dans ' . $daysLeft . ' jour(s). Un renouvellement est recommandé.';
        } else {
            $state = self::STATE_VALID;
            $message = 'Ce document est valide encore ' . $daysLeft . ' jour(s).';
        }

        return $this->result($state, $issued->toDateString(), $expiry->toDateString(), $daysLeft, true, $message);
    }

    /** Date de délivrance acceptée : format réel, pas dans le futur, pas avant 1900. */
    private function parseIssueDate(?string $value, Carbon $today): ?Carbon
    {
        if (blank($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
        // createFromFormat accepte les dates débordantes (ex. 2023-02-30) : on exige la date exacte.
        if ($date->toDateString() !== $value || $date->gt($today) || $date->year < 1900) {
            return null;
        }

        return $date;
    }

    /** @return array{state:string, issue_date:?string, expiry_date:?string, days_left:?int, can_compute:bool, message:string} */
    private function result(string $state, ?string $issue, ?string $expiry, ?int $daysLeft, bool $canCompute, string $message): array
    {
        return [
            'state' => $state,
            'issue_date' => $issue,
            'expiry_date' => $expiry,
            'days_left' => $daysLeft,
            'can_compute' => $canCompute,
            'message' => $message,
        ];
    }
}
