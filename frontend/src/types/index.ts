export interface Categorie {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  ministry?: string | null;
  icon: string;
  color: string;
  display_order: number;
  is_active?: boolean;
  demarches_count?: number;
}

export interface Demarche {
  id: number;
  title: string;
  slug: string;
  summary: string | null;
  description: string;
  required_documents: string[];
  procedure_steps: string[];
  conditions: string[];
  cost: string;
  cost_amount: number | null;
  processing_time: string | null;
  validity: string | null;
  processing_time_days: number | null;
  institution: string;
  /** Organisme compétent : commune, juridiction/province ou organisme national (null : non précisé). */
  authority_level?: 'local' | 'regional' | 'national' | null;
  location: string | null;
  /** Ville à laquelle se rapporte « location » (null : valable partout). */
  location_city?: { slug: string; name: string } | null;
  /** Administration uniquement : identifiant de la ville du lieu. */
  location_city_id?: number | null;
  contact_email: string | null;
  contact_phone: string | null;
  official_source_url: string | null;
  last_verified_at?: string | null;
  status: 'draft' | 'published' | 'archived';
  is_online: boolean;
  online_url: string | null;
  views_count: number;
  categorie?: Categorie;
  translated_content?: Record<string, unknown>;
  extra_costs?: ExtraCost[];
  cost_estimate?: CostEstimate;
  offices?: Office[];
  // Administration uniquement
  extra_costs_raw?: ExtraCostRaw[];
  office_ids?: number[];
  created_at?: string;
  updated_at?: string;
}

export interface Signalement {
  id: number;
  demarche_id: number | null;
  type: string;
  author_name: string | null;
  author_email: string | null;
  author_phone: string | null;
  message: string;
  status: 'pending' | 'investigating' | 'resolved' | 'rejected';
  admin_notes: string | null;
  demarche_title?: string;
  created_at: string;
}

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: 'super_admin' | 'admin' | 'editor';
}

/** Frais annexe affiché (libellé dans la langue demandée). */
export interface ExtraCost {
  label: string;
  amount: number;
}

/** Frais annexe tel que saisi dans l'administration (libellés dans les trois langues). */
export interface ExtraCostRaw {
  label: string;
  label_en: string | null;
  label_ar: string | null;
  amount: number;
}

/** Estimation du coût total : complete vaut false si le prix officiel n'est pas un montant unique. */
export interface CostEstimate {
  official: number | null;
  extras: number;
  total: number;
  complete: boolean;
}

export interface Office {
  id: number;
  name: string;
  address: string | null;
  opening_hours: string | null;
  phone: string | null;
  latitude: number | null;
  longitude: number | null;
  /** Null quand ni coordonnées ni adresse ne sont connues (pas d’itinéraire hasardeux). */
  directions_url: string | null;
  source_url?: string | null;
  is_active: boolean;
  /** Statut du service pour cette démarche : confirmé (officiel), signalé par la presse, ou à vérifier. */
  service_status?: 'confirmed' | 'press_reported' | 'to_verify';
  service_source_url?: string | null;
  service_verified_on?: string | null;
  /** « not_confirmed » : bureau d'une autre localité, compétence territoriale non confirmée. */
  competence?: 'not_confirmed';
  city?: { id: number; slug: string; name: string };
  // Administration uniquement
  raw?: Record<string, string | number | null>;
  demarche_ids?: number[];
}

export interface City {
  id: number;
  slug: string;
  name: string;
  province: string | null;
  offices_count: number;
}


/** Lieu compétent pour la ville choisie, renvoyé par l'API (source unique pour le site et les lecteurs). */
export interface PlaceForCity {
  city: { slug: string; name: string } | null;
  authority_level: 'local' | 'regional' | 'national' | null;
  offices: Office[];
  written_place: string | null;
  written_place_city: string | null;
  offices_available_in: Array<{ slug: string; name: string }>;
  elsewhere_offices: Office[];
  has_local_office: boolean;
  has_verified_place: boolean;
}
