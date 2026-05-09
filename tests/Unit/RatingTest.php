<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use Tests\TestCase;

class RatingTest extends TestCase
{
    private User $client;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create(['role' => 'client']);
        $this->booking = Booking::factory()->create(['user_id' => $this->client->id]);
    }

    public function test_rating_can_be_created()
    {
        $rating = Rating::factory()->create([
            'booking_id' => $this->booking->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertDatabaseHas('ratings', [
            'id' => $rating->id,
            'booking_id' => $this->booking->id,
        ]);
    }

    public function test_rating_stores_score()
    {
        $rating = Rating::factory()->create(['stars' => 5]);
        $this->assertEquals(5, $rating->stars);
    }

    public function test_rating_stores_review_text()
    {
        $review = 'Excellent service, very professional staff!';
        $rating = Rating::factory()->create(['comment' => $review]);

        $this->assertEquals($review, $rating->comment);
    }

    public function test_rating_can_include_photo()
    {
        $rating = Rating::factory()->create(['photo' => 'ratings/photo.jpg']);
        $this->assertNotNull($rating->photo);
        $this->assertStringContainsString('photo', $rating->photo);
    }

    public function test_rating_belongs_to_booking()
    {
        $rating = Rating::factory()->create([
            'booking_id' => $this->booking->id,
        ]);

        $this->assertTrue($rating->booking->is($this->booking));
    }

    public function test_rating_belongs_to_client()
    {
        $rating = Rating::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $this->assertTrue($rating->client->is($this->client));
    }

    public function test_rating_score_is_between_1_and_5()
    {
        for ($score = 1; $score <= 5; $score++) {
            $rating = Rating::factory()->create(['stars' => $score]);
            $this->assertGreaterThanOrEqual(1, $rating->stars);
            $this->assertLessThanOrEqual(5, $rating->stars);
        }
    }

    public function test_average_rating_calculation()
    {
        $booking1 = $this->booking;
        $booking2 = Booking::factory()->create(['user_id' => $this->client->id]);

        Rating::factory()->create(['booking_id' => $booking1->id, 'stars' => 5]);
        Rating::factory()->create(['booking_id' => $booking2->id, 'stars' => 3]);

        $ratings = Rating::all();
        $averageScore = $ratings->avg('stars');

        $this->assertEquals(4, $averageScore);
    }
}
