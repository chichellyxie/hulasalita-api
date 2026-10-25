<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use App\Models\Puzzle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Starter puzzles for testing. The QA member should review all content,
 * especially the Pangasinan words, before the pilot.
 *
 * Conventions:
 *  - language_id = the language of the expected ANSWER.
 *  - daily:     prompt describes the word; answer is a 5-letter word.
 *  - scramble:  prompt holds ONLY the scrambled letters (the app draws tiles).
 *  - translate: prompt states the word and the target language.
 */
class PuzzleSeeder extends Seeder
{
    private array $languages = [];

    private array $categories = [];

    public function run(): void
    {
        $this->languages = Language::pluck('id', 'code')->all();
        $this->categories = Category::pluck('id', 'name')->all();

        $this->seedDailyWords();
        $this->seedBugtong();
        $this->seedScrambles();
        $this->seedTranslations();
    }

    /** 14 daily puzzles starting today (Asia/Manila), one per date. */
    private function seedDailyWords(): void
    {
        $words = [
            ['pag', 'Nature', 'medium', 'danum', 'Inumin; kailangan ng lahat ng may buhay.'],
            ['en',  'Home',   'easy',   'house', 'A building where a family lives.'],
            ['fil', 'Nature', 'easy',   'tubig', 'Lumalabas sa gripo.'],
            ['pag', 'Home',   'medium', 'abong', 'Dito nakatira ang pamilya.'],
            ['en',  'Food',   'easy',   'bread', 'Baked from flour; pandesal is one kind.'],
            ['fil', 'Home',   'easy',   'bahay', 'Tirahan ng pamilya.'],
            ['pag', 'Nature', 'hard',   'bulan', 'Lumiliwanag sa langit tuwing gabi.'],
            ['en',  'Nature', 'medium', 'plant', 'It grows from a seed.'],
            ['fil', 'School', 'medium', 'aklat', 'Binabasa sa silid-aralan.'],
            ['pag', 'Food',   'hard',   'kanen', 'Lutong bigas, kasama ng ulam.'],
            ['en',  'School', 'medium', 'chalk', 'Used to write on a blackboard.'],
            ['fil', 'Food',   'medium', 'gatas', 'Puting inumin mula sa baka o kalabaw.'],
            ['pag', 'Home',   'hard',   'dalan', 'Dinaraanan ng tao at sasakyan.'],
            ['pag', 'Home',   'hard',   'baley', 'Bayan o lugar na tinitirhan.'],
        ];

        $names = ['en' => 'English', 'fil' => 'Filipino', 'pag' => 'Pangasinan'];
        $date = Carbon::now(Puzzle::TIMEZONE)->startOfDay();

        foreach ($words as $i => [$lang, $category, $difficulty, $answer, $hint]) {
            $length = mb_strlen($answer);

            Puzzle::updateOrCreate(
                ['scheduled_for' => $date->copy()->addDays($i)->toDateString()],
                [
                    'language_id' => $this->languages[$lang],
                    'category_id' => $this->categories[$category],
                    'type' => Puzzle::TYPE_DAILY,
                    'difficulty' => $difficulty,
                    'prompt' => "Guess today's {$length}-letter {$names[$lang]} word.",
                    'hint' => $hint,
                    'answer' => $answer,
                    'max_tries' => 6,
                ],
            );
        }
    }

    private function seedBugtong(): void
    {
        $riddles = [
            // Filipino bugtong
            ['fil', 'Objects', 'easy',   'Isang butil ng palay, sakop ang buong bahay.', 'Binubuksan kapag madilim.', 'ilaw'],
            ['fil', 'Animals', 'easy',   'Maliit pa si kumpare, nakakaakyat na sa tore.', 'Mahilig sa matamis.', 'langgam'],
            ['fil', 'Objects', 'medium', 'Nagbibigay na, sinasakal pa.', 'Lalagyan ng inumin.', 'bote'],
            ['fil', 'Food',    'medium', 'Baboy ko sa Marungko, balahibo ay pako.', 'Prutas na matinik ang balat.', 'langka'],
            ['fil', 'Food',    'hard',   'Isang prinsesa, nakaupo sa tasa.', 'Ang buto ay nasa labas ng bunga.', 'kasoy'],
            ['fil', 'Home',    'hard',   'Bahay ni Kaka, hindi matingala.', 'Bahagi ng mukha.', 'noo'],
            // English riddles
            ['en', 'Objects', 'easy',   'What has hands but cannot clap?', 'It tells you the time.', 'clock'],
            ['en', 'Objects', 'medium', 'What has many keys but cannot open a single lock?', 'You play music on it.', 'piano'],
            ['en', 'Objects', 'medium', 'What has an eye but cannot see?', 'Used with thread.', 'needle'],
            ['en', 'Home',    'hard',   'What gets wetter the more it dries?', 'You use it after a bath.', 'towel'],
        ];

        foreach ($riddles as [$lang, $category, $difficulty, $prompt, $hint, $answer]) {
            $this->upsert(Puzzle::TYPE_BUGTONG, $lang, $category, $difficulty, $prompt, $hint, $answer);
        }
    }

    private function seedScrambles(): void
    {
        $scrambles = [
            ['en',  'School', 'easy',   'LOOHCS',   'A place of learning.',            'school'],
            ['en',  'School', 'easy',   'CLINEP',   'You write with it.',              'pencil'],
            ['en',  'Food',   'medium', 'ANABAN',   'A long yellow fruit.',            'banana'],
            ['en',  'School', 'hard',   'HETRACE',  'Leads the class.',                'teacher'],
            ['fil', 'Home',   'easy',   'YALIS',    'Inuupuan.',                       'silya'],
            ['fil', 'School', 'medium', 'LANARAPA', 'Dito nag-aaral ang mga bata.',    'paaralan'],
            ['fil', 'Food',   'medium', 'NUPAHAN',  'Kinakain sa gabi.',               'hapunan'],
            ['fil', 'School', 'hard',   'NAGIBKAI', 'Kasama mo sa laro at saya.',      'kaibigan'],
            ['pag', 'Home',   'easy',   'GNOBA',    'Bahay sa Pangasinan.',            'abong'],
            ['pag', 'Home',   'medium', 'NADAL',    'Daan sa Pangasinan.',             'dalan'],
            ['pag', 'Food',   'medium', 'NEKAN',    'Kanin sa Pangasinan.',            'kanen'],
            ['pag', 'Nature', 'hard',   'NEWBETI',  'Bituin sa Pangasinan.',           'bitewen'],
        ];

        foreach ($scrambles as [$lang, $category, $difficulty, $prompt, $hint, $answer]) {
            $this->upsert(Puzzle::TYPE_SCRAMBLE, $lang, $category, $difficulty, $prompt, $hint, $answer);
        }
    }

    private function seedTranslations(): void
    {
        $translations = [
            // Answer in Pangasinan
            ['pag', 'Nature',  'easy',   'Translate to Pangasinan: water',     'Tubig sa Filipino.',  'danum'],
            ['pag', 'Home',    'easy',   'Translate to Pangasinan: house',     'Bahay sa Filipino.',  'abong'],
            ['pag', 'Home',    'medium', 'Translate to Pangasinan: child',     'Bata sa Filipino.',   'ugaw'],
            ['pag', 'Nature',  'hard',   'Translate to Pangasinan: star',      'Bituin sa Filipino.', 'bitewen'],
            // Answer in Filipino
            ['fil', 'School',  'easy',   'Translate to Filipino: book',        'Binabasa.',           'aklat'],
            ['fil', 'School',  'easy',   'Translate to Filipino: friend',      'Kalaro at kasama.',   'kaibigan'],
            ['fil', 'Nature',  'medium', 'Translate to Filipino: moon',        'Makikita sa gabi.',   'buwan'],
            // Answer in English
            ['en',  'Nature',  'easy',   'Translate to English: tubig',        'You drink it.',       'water'],
            ['en',  'School',  'easy',   'Translate to English: paaralan',     'A place of learning.', 'school'],
            ['en',  'Animals', 'easy',   'Translate to English: aso',          'A loyal pet.',        'dog'],
            ['en',  'Food',    'medium', 'Translate to English (from Pangasinan): asin', 'It makes food salty.', 'salt'],
            ['en',  'Home',    'medium', 'Translate to English (from Pangasinan): baley', 'Bigger than a barangay.', 'town'],
        ];

        foreach ($translations as [$lang, $category, $difficulty, $prompt, $hint, $answer]) {
            $this->upsert(Puzzle::TYPE_TRANSLATE, $lang, $category, $difficulty, $prompt, $hint, $answer);
        }
    }

    private function upsert(
        string $type,
        string $lang,
        string $category,
        string $difficulty,
        string $prompt,
        string $hint,
        string $answer,
    ): void {
        Puzzle::updateOrCreate(
            ['type' => $type, 'language_id' => $this->languages[$lang], 'prompt' => $prompt],
            [
                'category_id' => $this->categories[$category],
                'difficulty' => $difficulty,
                'hint' => $hint,
                'answer' => $answer,
                'max_tries' => 4,
                'scheduled_for' => null,
            ],
        );
    }
}
