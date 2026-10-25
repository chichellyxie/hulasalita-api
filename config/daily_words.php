<?php

/*
|--------------------------------------------------------------------------
| Daily Word Guess — word bank
|--------------------------------------------------------------------------
| Used by DailyPuzzleScheduler when a date has no daily puzzle yet.
| Each entry: [answer (letters only), hint, category, difficulty].
| Category must match a name in the categories table (created if missing).
| The QA member should review these words, especially Pangasinan.
| A word is not reused within 60 days of the same word being scheduled.
*/

return [

    'en' => [
        ['house', 'A building where a family lives.', 'Home', 'easy'],
        ['bread', 'Baked from flour; pandesal is one kind.', 'Food', 'easy'],
        ['plant', 'It grows from a seed.', 'Nature', 'medium'],
        ['chalk', 'Used to write on a blackboard.', 'School', 'medium'],
        ['apple', 'A red or green fruit.', 'Food', 'easy'],
        ['beach', 'Where sand and waves meet.', 'Nature', 'easy'],
        ['cloud', 'White and fluffy, it floats in the sky.', 'Nature', 'easy'],
        ['river', 'Flowing water, like the Agno.', 'Nature', 'medium'],
        ['tiger', 'A big striped cat.', 'Animals', 'medium'],
        ['horse', 'An animal people ride.', 'Animals', 'easy'],
        ['mango', 'A sweet yellow fruit loved in the Philippines.', 'Food', 'easy'],
        ['paper', 'You write on it.', 'School', 'easy'],
        ['table', 'You eat on it.', 'Home', 'easy'],
        ['chair', 'You sit on it.', 'Home', 'easy'],
        ['clock', 'It tells the time.', 'Objects', 'easy'],
        ['light', 'It helps you see in the dark.', 'Home', 'medium'],
        ['grass', 'Green and covers the ground.', 'Nature', 'easy'],
        ['stone', 'A small rock.', 'Nature', 'medium'],
        ['lemon', 'A sour yellow fruit.', 'Food', 'medium'],
        ['onion', 'It can make you cry when you cut it.', 'Food', 'medium'],
        ['sugar', 'It makes food sweet.', 'Food', 'easy'],
        ['piano', 'An instrument with black and white keys.', 'Objects', 'medium'],
        ['ruler', 'Used to measure and draw straight lines.', 'School', 'medium'],
        ['pencil', 'You write with it and can erase mistakes.', 'School', 'medium'],
        ['school', 'A place of learning.', 'School', 'easy'],
        ['bottle', 'It holds water or juice.', 'Objects', 'medium'],
        ['garden', 'Where flowers and vegetables grow.', 'Home', 'hard'],
        ['rabbit', 'An animal with long ears that hops.', 'Animals', 'hard'],
        ['monkey', 'It climbs trees and loves bananas.', 'Animals', 'hard'],
        ['teacher', 'Leads the class.', 'School', 'hard'],
    ],

    'fil' => [
        ['bahay', 'Tirahan ng pamilya.', 'Home', 'easy'],
        ['tubig', 'Lumalabas sa gripo.', 'Nature', 'easy'],
        ['aklat', 'Binabasa sa silid-aralan.', 'School', 'medium'],
        ['gatas', 'Puting inumin.', 'Food', 'medium'],
        ['kanin', 'Lutong bigas.', 'Food', 'easy'],
        ['lapis', 'Panulat na nabubura.', 'School', 'easy'],
        ['papel', 'Dito ka sumusulat.', 'School', 'easy'],
        ['silya', 'Inuupuan.', 'Home', 'easy'],
        ['dagat', 'Malawak na tubig-alat.', 'Nature', 'easy'],
        ['langit', 'Asul sa itaas natin.', 'Nature', 'medium'],
        ['bituin', 'Kumikislap sa gabi.', 'Nature', 'medium'],
        ['hangin', 'Hindi nakikita pero nararamdaman.', 'Nature', 'medium'],
        ['bigas', 'Hilaw pa ito bago maging kanin.', 'Food', 'easy'],
        ['itlog', 'Galing sa manok.', 'Food', 'easy'],
        ['gulay', 'Masustansyang pagkain mula sa halaman.', 'Food', 'medium'],
        ['pinto', 'Dinadaanan papasok ng bahay.', 'Home', 'easy'],
        ['bundok', 'Mataas na anyong lupa.', 'Nature', 'medium'],
        ['saging', 'Mahaba at dilaw na prutas.', 'Food', 'easy'],
        ['mangga', 'Matamis na dilaw na prutas.', 'Food', 'easy'],
        ['kalabaw', 'Katuwang ng magsasaka sa bukid.', 'Animals', 'hard'],
        ['kutsara', 'Ginagamit sa pagsubo ng sabaw.', 'Objects', 'hard'],
        ['bintana', 'Dito pumapasok ang liwanag sa bahay.', 'Home', 'hard'],
        ['paaralan', 'Dito nag-aaral ang mga bata.', 'School', 'hard'],
        ['ulan', 'Tubig na bumabagsak mula sa ulap.', 'Nature', 'easy'],
        ['araw', 'Nagbibigay ng liwanag sa umaga.', 'Nature', 'easy'],
        ['ibon', 'May pakpak at lumilipad.', 'Animals', 'easy'],
        ['isda', 'Nakatira sa tubig.', 'Animals', 'easy'],
        ['guro', 'Nagtuturo sa paaralan.', 'School', 'easy'],
    ],

    'pag' => [
        ['danum', 'Tubig sa Pangasinan.', 'Nature', 'medium'],
        ['abong', 'Bahay sa Pangasinan.', 'Home', 'medium'],
        ['bulan', 'Buwan sa Pangasinan.', 'Nature', 'hard'],
        ['kanen', 'Kanin sa Pangasinan.', 'Food', 'hard'],
        ['dalan', 'Daan sa Pangasinan.', 'Home', 'hard'],
        ['baley', 'Bayan sa Pangasinan.', 'Home', 'hard'],
        ['bitewen', 'Bituin sa Pangasinan.', 'Nature', 'hard'],
        ['asin', 'Pampaalat sa pagkain.', 'Food', 'easy'],
        ['ugaw', 'Bata sa Pangasinan.', 'Home', 'medium'],
        ['agew', 'Araw sa Pangasinan.', 'Nature', 'medium'],
        ['manok', 'Hayop na tumitilaok tuwing umaga.', 'Animals', 'easy'],
    ],

];
