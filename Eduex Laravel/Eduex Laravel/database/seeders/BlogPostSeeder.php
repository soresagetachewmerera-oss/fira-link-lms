<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryLookup = BlogCategory::query()
            ->pluck('id', 'slug')
            ->all();

        $posts = [
            [
                'title' => 'Fostering Creativity in Early Childhood Education',
                'slug' => 'fostering-creativity-in-early-childhood-education',
                'category' => 'Education',
                'author_name' => 'Eduex Editorial',
                'excerpt' => 'Explore how the “Beyond the Screen” marketplace reimagines digital product experiences for young learners and educators alike.',
                'content' => <<<'HTML'
<p>In the realm of digital innovation, the demand for creative and immersive experiences extends far beyond the confines of the screen. At Rethink, we are excited to unveil our latest project, the “Beyond the Screen Digital Products Marketplace,” a platform where the boundaries of digital design are transcended. In this blog post, we delve into the intricate process of crafting the design for this groundbreaking marketplace that takes users on a journey beyond conventional digital experiences.</p>
<p>The vision behind the “Beyond the Screen” marketplace was clear from the start – to redefine the way users interact with digital products. We envisioned a space where users could not only browse and purchase digital goods but also immerse themselves in a visually captivating environment that tells a story with every interaction.</p>
<blockquote>
    <p>“Eduex team quickly understood our business requirements and were proactive and flexible with our ongoing support developments. You can definitely trust them for complex projects.”</p>
    <footer>─ Tondu Porter</footer>
</blockquote>
<p>The cornerstone of our design philosophy for the marketplace was a deep understanding of user needs and behaviors. We conducted extensive user research to identify pain points in existing digital marketplaces and sought to address these through thoughtful design solutions.</p>
<p>From responsive layouts to accessible navigation patterns, every element was refined through multiple iterations and collaborative design reviews. The result is an experience that feels both familiar and delightfully unexpected.</p>
HTML,
                'featured_image' => 'assets/front/img/inner/news/news-05.jpg',
                'tags' => ['education', 'product', 'agency'],
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now()->subDays(7),
                'meta' => [
                    'title' => 'Fostering Creativity in Early Childhood Education',
                    'description' => 'How the “Beyond the Screen” marketplace reimagines digital product experiences for young learners.',
                ],
            ],
            [
                'title' => 'Simple Home Upgrades That Boost Property Value',
                'slug' => 'simple-home-upgrades-that-boost-property-value',
                'category' => 'Lifestyle',
                'author_name' => 'Nathan Fields',
                'excerpt' => 'Strategic updates can transform any living space. These approachable upgrades deliver real value without overwhelming budgets.',
                'content' => <<<'HTML'
<p>Whether you are preparing to sell or simply want to refresh your surroundings, home upgrades do not have to be disruptive. Start by focusing on high-impact areas like entryways and shared living spaces. A fresh coat of paint, updated lighting fixtures, or thoughtfully staged storage solutions can elevate the entire atmosphere.</p>
<p>Consider introducing smart technology in measured steps. Programmable thermostats, intelligent lighting, and integrated security systems make homes feel modern and efficient. Equally important, these upgrades allow homeowners to showcase sustainability and long-term cost savings.</p>
<p>Finally, remember that curb appeal matters. Simple landscaping touches, such as seasonal planters or a well-defined pathway, can make a lasting first impression.</p>
HTML,
                'featured_image' => 'assets/front/img/inner/news/news-06.jpg',
                'tags' => ['home', 'design', 'tips'],
                'reading_time_minutes' => 4,
                'is_published' => true,
                'published_at' => now()->subDays(14),
                'meta' => [
                    'title' => 'Simple Home Upgrades That Boost Property Value',
                    'description' => 'High-impact improvements to elevate your living spaces and property value.',
                ],
            ],
            [
                'title' => 'Working Together for Student Best Practices',
                'slug' => 'working-together-for-student-best-practices',
                'category' => 'Education',
                'author_name' => 'Samantha Lee',
                'excerpt' => 'Collaboration between families and educators forms the backbone of student success. Discover frameworks that make partnership practical.',
                'content' => <<<'HTML'
<p>Supporting students effectively requires open communication and shared responsibility. Educators bring expertise in pedagogy, while families contribute deep insight into personal motivations and challenges. When both sides align goals and establish clear feedback loops, students thrive.</p>
<p>One proven approach is to structure quarterly learning plans that outline objectives, support resources, and opportunities for celebration. Transparent expectations reduce anxiety and allow students to focus energy on meaningful progress.</p>
<p>Schools can also host regular community forums, giving families a voice in shaping programming. These gatherings surface new ideas and highlight the diversity of experiences within the learning ecosystem.</p>
HTML,
                'featured_image' => 'assets/front/img/inner/news/news-07.jpg',
                'tags' => ['education', 'community'],
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now()->subDays(21),
                'meta' => [
                    'title' => 'Working Together for Student Best Practices',
                    'description' => 'Frameworks and rituals that help educators and families collaborate effectively.',
                ],
            ],
            [
                'title' => 'Building Resilience and Empathy in the Classroom',
                'slug' => 'building-resilience-and-empathy-in-the-classroom',
                'category' => 'Teaching',
                'author_name' => 'Marcus Brown',
                'excerpt' => 'Emotional intelligence underpins academic excellence. Explore classroom rituals that strengthen empathy and resilience.',
                'content' => <<<'HTML'
<p>Today’s classrooms operate in rapidly changing environments. Students negotiate social pressures, shifting expectations, and global uncertainties. Educators who intentionally cultivate empathy and resilience empower learners to navigate these realities with confidence.</p>
<p>Begin with daily check-ins that invite honest reflection. These moments help students name emotions and normalize seeking help. Complement the practice with collaborative projects that require active listening and shared accountability.</p>
<p>Resilience emerges when learners embrace challenges. Provide structured opportunities to iterate, celebrate incremental progress, and analyze setbacks without judgment.</p>
HTML,
                'featured_image' => 'assets/front/img/inner/news/news-03.jpg',
                'tags' => ['classroom', 'empathy'],
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now()->subDays(28),
                'meta' => [
                    'title' => 'Building Resilience and Empathy in the Classroom',
                    'description' => 'Practices that nurture emotionally intelligent learning environments.',
                ],
            ],
            [
                'title' => 'The Impact of Technology in Modern Classrooms',
                'slug' => 'the-impact-of-technology-in-modern-classrooms',
                'category' => 'Technology',
                'author_name' => 'Claire Mitchell',
                'excerpt' => 'Technology can extend the reach of traditional instruction. Here’s how to integrate tools without losing human connection.',
                'content' => <<<'HTML'
<p>Digital platforms have redefined how students access information and demonstrate mastery. Interactive simulations, adaptive assessments, and collaborative workspaces empower learners to explore concepts at their own pace.</p>
<p>However, adoption should be intentional. Start with a clear pedagogical goal, then evaluate whether a given tool simplifies workflows or introduces unnecessary complexity. Ongoing professional development ensures educators feel confident leveraging new capabilities.</p>
<p>Above all, balance screen time with hands-on experimentation, discussion, and reflection. Technology should amplify—not replace—the human relationships at the heart of education.</p>
HTML,
                'featured_image' => 'assets/front/img/inner/news/news-04.jpg',
                'tags' => ['technology', 'education'],
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now()->subDays(35),
                'meta' => [
                    'title' => 'The Impact of Technology in Modern Classrooms',
                    'description' => 'Guidance for thoughtfully integrating digital tools into everyday instruction.',
                ],
            ],
            [
                'title' => 'Student Achievement Best Practices for Schools and Families',
                'slug' => 'student-achievement-best-practices',
                'category' => 'Education',
                'author_name' => 'Harper Collins',
                'excerpt' => 'Academic growth flourishes when support networks stay aligned. These best practices keep everyone focused on student milestones.',
                'content' => <<<'HTML'
<p>Achievement is not just measured by test scores. It reflects confidence, curiosity, and a lifelong love of learning. Schools and families can champion these qualities by designing routines that reinforce organization, goal setting, and reflection.</p>
<p>Create shared calendars that highlight upcoming assignments and celebrations. Celebrate effort as much as results to encourage perseverance. When students encounter obstacles, involve them in designing solutions to build ownership.</p>
<p>Lastly, maintain regular touchpoints between teachers and caregivers. Brief messages, weekly summaries, or informal conversations sustain momentum and reinforce a shared commitment to growth.</p>
HTML,
                'featured_image' => 'assets/front/img/inner/news/news-02.jpg',
                'tags' => ['students', 'growth'],
                'reading_time_minutes' => 4,
                'is_published' => true,
                'published_at' => now()->subDays(42),
                'meta' => [
                    'title' => 'Student Achievement Best Practices for Schools and Families',
                    'description' => 'Collaborative habits that keep student progress on track all year long.',
                ],
            ],
        ];

        foreach ($posts as $postData) {
            $postData['excerpt'] ??= Str::limit(strip_tags($postData['content']), 240);

            $categorySlug = Str::slug($postData['category'] ?? '');
            $categoryId = $categorySlug !== '' ? Arr::get($categoryLookup, $categorySlug) : null;

            BlogPost::updateOrCreate(
                ['slug' => $postData['slug']],
                array_merge($postData, ['blog_category_id' => $categoryId])
            );
        }

        $firstPost = BlogPost::where('slug', 'fostering-creativity-in-early-childhood-education')->first();

        if ($firstPost && $firstPost->comments()->doesntExist()) {
            $createdComments = $firstPost->comments()->createMany([
                [
                    'author_name' => 'Mariya Dsuza',
                    'author_email' => 'mariya@example.com',
                    'author_website' => null,
                    'body' => 'This perspective on immersive learning experiences is inspiring. I appreciate the emphasis on storytelling within digital products!',
                    'is_approved' => true,
                    'ip_address' => '127.0.0.1',
                ],
                [
                    'author_name' => 'Michel Alex',
                    'author_email' => 'michel@example.com',
                    'author_website' => 'https://example.org',
                    'body' => 'Great insights. We have seen similar engagement increases after introducing marketplace walkthroughs.',
                    'is_approved' => true,
                    'ip_address' => '127.0.0.1',
                ],
            ]);

            if ($createdComments->isNotEmpty()) {
                $firstComment = $createdComments->first();

                BlogComment::create([
                    'blog_post_id' => $firstPost->id,
                    'parent_id' => $firstComment->id,
                    'author_name' => 'Tondu Porter',
                    'author_email' => 'tondu@example.com',
                    'author_website' => null,
                    'body' => 'Thanks for the kind words! Our team is excited to see these ideas resonate with other educators.',
                    'is_approved' => true,
                    'ip_address' => '127.0.0.1',
                ]);
            }
        }
    }
}
