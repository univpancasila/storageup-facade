<?php

use Univpancasila\StorageUp\Models\StorageFile;

/**
 * Architecture Tests
 *
 * @author @abdansyakuro.id
 */

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

arch('models should extend Eloquent Model')
    ->expect('Univpancasila\StorageUp\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('facades should extend Laravel Facade')
    ->expect('Univpancasila\StorageUp\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade');

arch('service classes should implement their interface')
    ->expect('Univpancasila\StorageUp\StorageUpService')
    ->toImplement('Univpancasila\StorageUp\StorageUp');

arch('ensure models are not abstract')
    ->expect('Univpancasila\StorageUp\Models')
    ->not->toBeAbstract();

arch('ensure services are not abstract except interfaces')
    ->expect('Univpancasila\StorageUp')
    ->not->toBeAbstract()
    ->ignoring('Univpancasila\StorageUp\StorageUp');

arch('package classes should not use global functions')
    ->expect('Univpancasila\StorageUp')
    ->not->toUse([
        'die',
        'exit',
        'eval',
    ]);

arch('ensure proper namespacing')
    ->expect('Univpancasila\StorageUp')
    ->toOnlyBeUsedIn([
        'Univpancasila\StorageUp',
        'Univpancasila\StorageUp\Tests',
    ]);
