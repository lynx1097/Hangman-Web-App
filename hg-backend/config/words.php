<?php

/*
|--------------------------------------------------------------------------
| Hangman Word List (offline fallback)
|--------------------------------------------------------------------------
|
| The primary word source is the Word Game DB API (see App\Services\WordService).
| This list is only used when that API is unreachable or its daily quota is
| exhausted. It deliberately mirrors the API's word-object shape
| (word / category / numLetters / numSyllables / hint) so the fallback is
| seamless — the player can't tell which source produced the word.
|
| Categories match the API's set: animal, country, food, plant, sport.
| Words must be letters A-Z only so they mask/reveal cleanly.
|
*/

return [
    ['word' => 'hamster',   'category' => 'animal',  'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Pet rodent'],
    ['word' => 'elephant',  'category' => 'animal',  'numLetters' => 8, 'numSyllables' => 3, 'hint' => 'Largest land animal'],
    ['word' => 'dolphin',   'category' => 'animal',  'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Intelligent marine mammal'],
    ['word' => 'penguin',   'category' => 'animal',  'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Flightless bird of the cold'],
    ['word' => 'giraffe',   'category' => 'animal',  'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Tallest animal'],

    ['word' => 'canada',    'category' => 'country', 'numLetters' => 6, 'numSyllables' => 3, 'hint' => 'Home of the maple leaf'],
    ['word' => 'brazil',    'category' => 'country', 'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Largest country in South America'],
    ['word' => 'egypt',     'category' => 'country', 'numLetters' => 5, 'numSyllables' => 2, 'hint' => 'Land of the pyramids'],
    ['word' => 'japan',     'category' => 'country', 'numLetters' => 5, 'numSyllables' => 2, 'hint' => 'The land of the rising sun'],
    ['word' => 'germany',   'category' => 'country', 'numLetters' => 7, 'numSyllables' => 3, 'hint' => 'European country famous for cars'],

    ['word' => 'banana',    'category' => 'food',    'numLetters' => 6, 'numSyllables' => 3, 'hint' => 'Yellow curved fruit'],
    ['word' => 'pizza',     'category' => 'food',    'numLetters' => 5, 'numSyllables' => 2, 'hint' => 'Italian dish with toppings'],
    ['word' => 'avocado',   'category' => 'food',    'numLetters' => 7, 'numSyllables' => 4, 'hint' => 'Green fruit used in guacamole'],
    ['word' => 'noodle',    'category' => 'food',    'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Long strand of pasta'],
    ['word' => 'pancake',   'category' => 'food',    'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Flat breakfast cake'],

    ['word' => 'cactus',    'category' => 'plant',   'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Spiky desert plant'],
    ['word' => 'bamboo',    'category' => 'plant',   'numLetters' => 6, 'numSyllables' => 2, 'hint' => "A panda's favourite food"],
    ['word' => 'orchid',    'category' => 'plant',   'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Elegant flowering plant'],
    ['word' => 'maple',     'category' => 'plant',   'numLetters' => 5, 'numSyllables' => 2, 'hint' => 'Tree that gives syrup'],
    ['word' => 'fern',      'category' => 'plant',   'numLetters' => 4, 'numSyllables' => 1, 'hint' => 'Leafy shade-loving plant'],

    ['word' => 'soccer',    'category' => 'sport',   'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Played with a round ball and feet'],
    ['word' => 'tennis',    'category' => 'sport',   'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Racket sport with a net'],
    ['word' => 'boxing',    'category' => 'sport',   'numLetters' => 6, 'numSyllables' => 2, 'hint' => 'Combat sport with gloves'],
    ['word' => 'cricket',   'category' => 'sport',   'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Bat-and-ball sport with wickets'],
    ['word' => 'cycling',   'category' => 'sport',   'numLetters' => 7, 'numSyllables' => 2, 'hint' => 'Sport on two wheels'],
];
