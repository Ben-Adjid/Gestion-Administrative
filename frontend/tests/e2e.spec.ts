import { test, expect } from '@playwright/test';

test.describe('DemarchesTchad - Parcours Citoyen et E2E', () => {

  test('Page d’accueil présente le portail et permet d’ouvrir le catalogue', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/DemarchesTchad/);
    await expect(page.locator('h1')).toContainText('Vos démarches administratives');
    await expect(page.getByRole('link', { name: /Explorer les démarches/ })).toHaveAttribute('href', '/demarches');
    await expect(page.getByRole('main').getByRole('link', { name: 'Passeport' })).toHaveAttribute('href', '/demarches/passeport-biometrique');
    await expect(page.getByRole('link', { name: /Connexion \/ Login/ })).toHaveCount(0);
    await expect(page.locator('nav a[href="/villes"]')).toHaveCount(0);
  });

  test('Recherche et filtrage dans le catalogue des démarches', async ({ page }) => {
    const requestedUrls: string[] = [];
    await page.route('**/api/v1/categories**', (route) => route.fulfill({ json: { data: [] } }));
    await page.route('**/api/v1/analytics/page-views', (route) => route.fulfill({ status: 202, json: {} }));
    await page.route('**/api/v1/demarches**', (route) => {
      requestedUrls.push(route.request().url());
      return route.fulfill({ json: { data: [], meta: { last_page: 1 } } });
    });
    await page.goto('/demarches');
    await expect(page.locator('h1')).toContainText('Catalogue des démarches');
    
    const searchInput = page.getByRole('main').locator('input[placeholder*="Chercher"]').first();
    await searchInput.fill('Passeport');
    await expect(searchInput).toHaveValue('Passeport');
    await expect.poll(() => requestedUrls.some((url) => new URL(url).searchParams.get('q') === 'Passeport')).toBe(true);
  });

  test('Le catalogue public parcourt les démarches paginées', async ({ page }) => {
    const requestedPages: string[] = [];
    await page.route('**/api/v1/categories**', (route) => route.fulfill({ json: { data: [] } }));
    await page.route('**/api/v1/analytics/page-views', (route) => route.fulfill({ status: 202, json: {} }));
    await page.route('**/api/v1/demarches**', (route) => {
      const url = new URL(route.request().url());
      requestedPages.push(url.searchParams.get('page') || '1');
      const pageNumber = Number(url.searchParams.get('page') || '1');
      const demarches = Array.from({ length: pageNumber === 1 ? 12 : 1 }, (_, index) => ({
        id: (pageNumber - 1) * 12 + index + 1,
        title: `Démarche ${pageNumber}-${index + 1}`,
        slug: `demarche-${pageNumber}-${index + 1}`,
        summary: 'Formalité administrative de test',
        description: 'Description de test',
        required_documents: [],
        procedure_steps: [],
        conditions: [],
        cost: 'Gratuit',
        cost_amount: null,
        processing_time: '5 jours',
        processing_time_days: 5,
        institution: 'Administration test',
        location: null,
        contact_email: null,
        contact_phone: null,
        official_source_url: null,
        status: 'published',
        is_online: false,
        online_url: null,
        views_count: 0,
      }));
      return route.fulfill({
        json: { data: demarches, meta: { current_page: pageNumber, last_page: 2, total: 13 } },
      });
    });

    await page.goto('/demarches');
    await expect(page.getByRole('main').getByRole('heading', { name: 'Démarche 1-1' }).first()).toBeVisible();
    await page.getByRole('button', { name: /Suivant/ }).click();
    await expect(page.getByRole('main').getByRole('heading', { name: 'Démarche 2-1' }).first()).toBeVisible();
    expect(requestedPages).toContain('2');
  });

  test('Formulaire citoyen de signalement d’anomalie', async ({ page }) => {
    await page.route('**/api/v1/signalements', (route) => route.fulfill({
      status: 201,
      json: { message: 'Signalement transmis', data: { id: 1 } },
    }));
    await page.goto('/signalement');
    await expect(page.locator('h1')).toContainText('Signaler une erreur');
    
    // Remplissage du formulaire
    await page.locator('select#type').selectOption('cout_different');
    await page.fill('textarea', 'Le tarif au guichet ne correspond pas au montant affiché.');
    await page.fill('input[type="email"]', 'citoyen.test@demarches.td');
    
    await page.locator('button[type="submit"]').click();
    await expect(page.getByRole('heading', { name: 'Signalement envoyé, merci !' })).toBeVisible();
  });

  test('Connexion admin utilise le backend et ouvre le tableau de bord', async ({ page }) => {
    await page.route('**/api/v1/admin/auth/login', (route) => route.fulfill({
      json: { token: 'test-token', admin: { id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin' } },
    }));
    await page.route('**/api/v1/admin/auth/me', (route) => route.fulfill({
      json: { admin: { id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } } },
    }));
    await page.route('**/api/v1/admin/stats', (route) => route.fulfill({
      json: {
        data: {
          role: 'admin',
          capabilities: {
            manage_categories: true,
            moderate_signalements: true,
            view_analytics: true,
            manage_team: false,
          },
          content: {
            published: 12,
            drafts: 3,
            archived: 1,
            needs_verification: 2,
            verification_max_age_days: 180,
            to_verify: [],
          },
          signalements: { pending: 2, investigating: 1, resolved_this_month: 4, recent: [] },
          validation_queue: [],
          audience: { visitors_today: 20, views_today: 35, top_demarches: [] },
        },
      },
    }));
    await page.route('**/api/v1/admin/demarches**', (route) => route.fulfill({ json: { data: [] } }));
    await page.route('**/api/v1/admin/categories', (route) => route.fulfill({ json: { data: [] } }));
    await page.route('**/api/v1/admin/signalements**', (route) => route.fulfill({ json: { data: [] } }));
    await page.goto('/admin/login');
    await expect(page.locator('h1')).toContainText('Administration');
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await page.locator('input[type="email"]').fill('admin@test.td');
    await page.locator('input[type="password"]').fill('Secret123!');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin\/dashboard$/);
    await expect(page.locator('h1')).toContainText('Tableau de bord');
    await expect(page.getByText('12', { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: /Catégories/ })).toBeVisible();
    await expect(page.getByRole('link', { name: /Équipe/ })).toHaveCount(0);
  });

  test('Un administrateur peut modifier une catégorie', async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.setItem('admin_token', 'test-token');
      localStorage.setItem('admin', JSON.stringify({ id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } }));
    });
    await page.route('**/api/v1/admin/auth/me', (route) => route.fulfill({ json: { admin: { id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } } } }));
    const category = {
      id: 1,
      name: 'Éducation',
      slug: 'education',
      description: 'Procédures éducatives',
      icon: 'book',
      color: '#2563EB',
      display_order: 1,
      is_active: true,
      demarches_count: 0,
    };
    let currentCategory = category;
    await page.route('**/api/v1/admin/categories**', async (route) => {
      if (route.request().method() === 'PUT') {
        currentCategory = { ...category, name: 'Formation' };
        await route.fulfill({ json: { message: 'Catégorie mise à jour', data: currentCategory } });
        return;
      }
      await route.fulfill({ json: { data: [currentCategory], meta: { total: 1 } } });
    });

    await page.goto('/admin/categories');
    await page.getByRole('button', { name: /Modifier/ }).click();
    await page.getByLabel('Nom', { exact: false }).first().fill('Formation');
    await page.getByRole('button', { name: 'Enregistrer' }).click();

    await expect(page.getByRole('status').filter({ hasText: 'Catégorie mise à jour.' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Formation' })).toBeVisible();
  });

  test('Un administrateur peut créer et modifier une démarche du catalogue', async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.setItem('admin_token', 'test-token');
      localStorage.setItem('admin', JSON.stringify({ id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } }));
    });
    await page.route('**/api/v1/admin/auth/me', (route) => route.fulfill({ json: { admin: { id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } } } }));
    await page.addInitScript(() => localStorage.setItem('admin_token', 'test-token'));
    const category = {
      id: 1,
      name: 'Éducation',
      slug: 'education',
      description: null,
      icon: 'book',
      color: '#2563EB',
      display_order: 1,
      is_active: true,
    };
    let currentDemarche: Record<string, unknown> | null = null;

    await page.route('**/api/v1/admin/categories**', (route) =>
      route.fulfill({ json: { data: [category], meta: { total: 1 } } }),
    );
    await page.route('**/api/v1/admin/demarches**', async (route) => {
      const method = route.request().method();
      if (method === 'POST') {
        const payload = route.request().postDataJSON();
        currentDemarche = {
          ...payload,
          id: 1,
          slug: 'inscription-test',
          summary: payload.summary || null,
          cost_amount: payload.cost_amount ?? null,
          processing_time_days: payload.processing_time_days ?? null,
          categorie: category,
          views_count: 0,
        };
        await route.fulfill({ status: 201, json: { data: currentDemarche } });
        return;
      }
      if (method === 'PUT') {
        const payload = route.request().postDataJSON();
        currentDemarche = { ...currentDemarche, ...payload, title: 'Inscription modifiée' };
        await route.fulfill({ json: { data: currentDemarche } });
        return;
      }
      await route.fulfill({
        json: {
          data: currentDemarche ? [currentDemarche] : [],
          meta: { current_page: 1, last_page: 1, total: currentDemarche ? 1 : 0 },
        },
      });
    });

    await page.goto('/admin/demarches');
    await page.getByRole('button', { name: /Nouvelle démarche/ }).click();
    await page.getByLabel('Titre *').fill('Inscription test');
    await page.getByLabel('Catégorie *').selectOption('1');
    await page.getByLabel('Institution *').fill('Ministère test');
    await page.getByLabel('Coût affiché *').fill('Gratuit');
    await page.getByLabel('Description *').fill('Description de la démarche test.');
    await page.getByRole('button', { name: 'Créer la démarche' }).click();
    await expect(page.getByRole('heading', { name: 'Inscription test' })).toBeVisible();

    await page.getByRole('button', { name: /Modifier/ }).click();
    await page.getByLabel('Titre *').fill('Inscription modifiée');
    await page.getByRole('button', { name: 'Enregistrer les modifications' }).click();
    await expect(page.getByRole('heading', { name: 'Inscription modifiée' })).toBeVisible();
  });

  test('Un administrateur peut répondre à un signalement', async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.setItem('admin_token', 'test-token');
      localStorage.setItem('admin', JSON.stringify({ id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } }));
    });
    await page.route('**/api/v1/admin/auth/me', (route) => route.fulfill({ json: { admin: { id: 1, name: 'Admin test', email: 'admin@test.td', role: 'admin', capabilities: { edit_demarches: true, publish_demarches: true, archive_demarches: true, manage_categories: true, moderate_signalements: true, export_csv: true, view_analytics: true, manage_reference: true, moderate_community: true, verify_demarches: true, import_demarches: true, view_search_misses: true, manage_team: false, view_audit_log: false, manage_api_keys: false } } } }));
    const signalement = {
      id: 1,
      demarche_id: null,
      type: 'info_erronee',
      author_name: 'Citoyen test',
      author_email: 'citoyen@test.td',
      author_phone: null,
      message: 'La liste des documents demandés ne correspond pas à celle du guichet.',
      status: 'pending',
      admin_notes: null,
      demarche_title: null,
      created_at: '2026-10-01T10:00:00Z',
    };
    let updated = false;
    await page.route('**/api/v1/admin/signalements**', async (route) => {
      if (route.request().method() === 'PATCH') {
        updated = true;
        await route.fulfill({ json: { data: { ...signalement, status: 'investigating' } } });
        return;
      }
      await route.fulfill({ json: { data: [signalement], meta: { total: 1 } } });
    });

    await page.goto('/admin/signalements');
    await page.getByRole('button', { name: /Répondre/ }).click();
    await page.getByLabel(/Réponse au citoyen/).fill('Nous vérifions cette information.');
    await page.getByRole('button', { name: /Envoyer la réponse/ }).click();

    await expect(page.getByRole('status').filter({ hasText: 'Réponse enregistrée.' })).toBeVisible();
    expect(updated).toBe(true);
  });

});
