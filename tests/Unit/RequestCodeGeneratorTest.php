<?php

namespace Tests\Unit;

use App\Models\PresenterRequest;
use App\Models\User;
use App\Support\RequestCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_first_code_for_month(): void
    {
        $generator = new RequestCodeGenerator;

        $code = $generator->generate(new \DateTimeImmutable('2026-07-15'));

        $this->assertSame('PR-202607-0001', $code);
    }

    public function test_increments_sequence_within_same_month(): void
    {
        $user = User::factory()->create();

        PresenterRequest::create([
            'request_code' => 'PR-202607-0001',
            'pmb_period_id' => $this->createPmbPeriod()->id,
            'presenter_id' => $this->createPresenter()->id,
            'created_by' => $user->id,
            'status' => 'draft',
            'request_date' => '2026-07-01',
        ]);

        $generator = new RequestCodeGenerator;
        $code = $generator->generate(new \DateTimeImmutable('2026-07-20'));

        $this->assertSame('PR-202607-0002', $code);
    }

    public function test_resets_sequence_each_month(): void
    {
        $user = User::factory()->create();

        PresenterRequest::create([
            'request_code' => 'PR-202607-0099',
            'pmb_period_id' => $this->createPmbPeriod()->id,
            'presenter_id' => $this->createPresenter()->id,
            'created_by' => $user->id,
            'status' => 'draft',
            'request_date' => '2026-07-01',
        ]);

        $generator = new RequestCodeGenerator;
        $code = $generator->generate(new \DateTimeImmutable('2026-08-01'));

        $this->assertSame('PR-202608-0001', $code);
    }

    private function createPmbPeriod(): \App\Models\PmbPeriod
    {
        return \App\Models\PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'aktif',
        ]);
    }

    private function createPresenter(): \App\Models\Presenter
    {
        $category = \App\Models\PresenterCategory::create([
            'name' => 'Kategori A',
            'status' => 'aktif',
        ]);

        return \App\Models\Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter Test',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Test',
            'phone' => '081234567890',
            'status' => 'aktif',
        ]);
    }
}
