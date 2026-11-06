<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Schedule::command('documents:send-reminders')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('report:monthly')->monthlyOn(1, '07:00')->withoutOverlapping();
Schedule::command('demarches:verification-reminder')->weeklyOn(1, '07:30')->withoutOverlapping();
Schedule::command('demarches:publish-scheduled')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment('DemarchesTchad - Servir la Nation avec intégrité.');
})->purpose('Devise officielle');

/*
 * Crée un compte d'équipe ou réinitialise son mot de passe (et le déverrouille).
 * Le mot de passe est saisi de façon masquée : jamais de valeur par défaut, jamais affiché.
 */
Artisan::command('admin:create {email} {--role=super_admin} {--name=Administrateur}', function (string $email) {
    $role = (string) $this->option('role');
    if (!in_array($role, Admin::ROLES, true)) {
        $this->error('Rôle invalide. Valeurs possibles : ' . implode(', ', Admin::ROLES));
        return 1;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Adresse email invalide.');
        return 1;
    }

    $password = (string) $this->secret('Nouveau mot de passe (12 caractères min., majuscule, chiffre, symbole)');
    $confirmation = (string) $this->secret('Confirmez le mot de passe');

    $validator = Validator::make(
        ['password' => $password, 'password_confirmation' => $confirmation],
        ['password' => ['required', 'confirmed', 'min:12', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/']],
        [
            'password.confirmed' => 'Les deux saisies ne correspondent pas.',
            'password.min' => 'Au moins 12 caractères.',
            'password.regex' => 'Il faut une majuscule, une minuscule, un chiffre et un symbole.',
        ]
    );
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }
        return 1;
    }

    $admin = Admin::withTrashed()->firstOrNew(['email' => $email]);
    $isNew = !$admin->exists;
    $admin->forceFill([
        'name' => $isNew ? (string) $this->option('name') : $admin->name,
        'role' => $role,
        'password' => Hash::make($password),
        'is_active' => true,
        'failed_login_attempts' => 0,
        'locked_until' => null,
        'deleted_at' => null,
    ])->save();
    $admin->tokens()->delete();

    $this->info(($isNew ? 'Compte créé' : 'Mot de passe réinitialisé') . " : {$admin->email} ({$role}). Connectez-vous sur /admin/login.");
    return 0;
})->purpose('Créer un compte d’équipe ou réinitialiser son mot de passe');
