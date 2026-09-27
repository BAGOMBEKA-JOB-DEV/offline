<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ChallengePayload;
use Tests\TestCase;
class DbgTest extends TestCase {
    use RefreshDatabase;
    public function test_dbg(): void {
        $r = $this->postJson('/api/v1/sync', ChallengePayload::envelope());
        fwrite(STDERR, "\nDISPOSITIONS: ".json_encode($r->json('data.dispositions'), JSON_PRETTY_PRINT)."\n");
        $this->assertTrue(true);
    }
}
