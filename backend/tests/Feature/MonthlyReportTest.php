<?php

namespace Tests\Feature;

use App\Mail\MonthlyReportMail;
use App\Models\Admin;
use App\Services\MonthlyReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email, string $role, bool $subscribed, bool $active = true): Admin
    {
        $admin = Admin::create([
            'name' => $email, 'email' => $email, 'password' => Hash::make('Secret123!'),
            'role' => $role, 'is_active' => $active,
        ]);
        $admin->forceFill(['notify_admin_reports' => $subscribed])->save();
        return $admin;
    }

    public function test_report_is_sent_only_to_active_subscribed_moderators(): void
    {
        Mail::fake();
        $this->admin('super@test.td', 'super_admin', true);
        $this->admin('admin@test.td', 'admin', true);
        $this->admin('desabonne@test.td', 'admin', false);
        $this->admin('editeur@test.td', 'editor', true);
        $this->admin('inactif@test.td', 'super_admin', true, false);

        $this->artisan('report:monthly', ['--month' => '2026-09'])->assertSuccessful();

        Mail::assertQueued(MonthlyReportMail::class, 2);
        Mail::assertQueued(MonthlyReportMail::class, fn ($mail) => $mail->hasTo('super@test.td'));
        Mail::assertQueued(MonthlyReportMail::class, fn ($mail) => $mail->hasTo('admin@test.td'));
        Mail::assertNotQueued(MonthlyReportMail::class, fn ($mail) => $mail->hasTo('editeur@test.td'));
    }

    public function test_report_counts_only_the_requested_month_and_renders(): void
    {
        DB::table('page_views')->insert([
            ['path' => '/demarches/passeport', 'visitor_hash' => 'a', 'viewed_at' => '2026-09-10 10:00:00'],
            ['path' => '/demarches/passeport', 'visitor_hash' => 'b', 'viewed_at' => '2026-09-11 10:00:00'],
            ['path' => '/', 'visitor_hash' => 'a', 'viewed_at' => '2026-09-12 10:00:00'],
            ['path' => '/', 'visitor_hash' => 'c', 'viewed_at' => '2026-10-01 10:00:00'],
        ]);

        $report = app(MonthlyReport::class)->build(CarbonImmutable::create(2026, 9, 1));

        $this->assertSame(3, $report['page_views']);
        $this->assertSame(2, $report['unique_visitors']);
        $this->assertSame(2, $report['top_demarches'][0]['views']);

        $html = (new MonthlyReportMail($report))->render();
        $this->assertStringContainsString('septembre 2026', $html);
        $this->assertStringContainsString('Pages vues', $html);
    }

    public function test_invalid_month_is_rejected(): void
    {
        $this->artisan('report:monthly', ['--month' => '2026-13'])->assertFailed();
    }
}
