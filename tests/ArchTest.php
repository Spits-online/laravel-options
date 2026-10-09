<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();

arch('every file declares strict types')
    ->expect('SpitsOnline\Options')
    ->toUseStrictTypes();

arch('exceptions extend the package base exception')
    ->expect('SpitsOnline\Options\Exceptions')
    ->classes()
    ->toExtend('SpitsOnline\Options\Exceptions\OptionsException')
    ->ignoring('SpitsOnline\Options\Exceptions\OptionsException');

arch('commands are console commands')
    ->expect('SpitsOnline\Options\Console')
    ->classes()
    ->toExtend('Illuminate\Console\Command')
    ->toBeFinal();
