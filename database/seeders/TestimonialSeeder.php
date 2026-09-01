<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Starter testimonials for the marketing site. The client edits, removes or
 * adds their own in the Content Manager.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Ahmed R.', 'AE', 'Passed both phases in three weeks. Payout hit my USDT wallet two days after I requested it.', 5, true],
            ['Grace O.', 'GH', 'The rules are actually clear for once. No hidden consistency traps — just trade well and get funded.', 5, true],
            ['Daniel K.', 'KE', 'Support answered my account question within the hour. Smooth from checkout to funded.', 5, true],
            ['Marko P.', 'HR', 'Bought a 50K 2-step, funded on the second attempt. The dashboard makes tracking drawdown easy.', 4, false],
        ];

        foreach ($items as $i => [$name, $country, $body, $rating, $featured]) {
            Testimonial::updateOrCreate(
                ['author_name' => $name, 'body' => $body],
                [
                    'author_country' => $country,
                    'rating' => $rating,
                    'is_featured' => $featured,
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }
    }
}
