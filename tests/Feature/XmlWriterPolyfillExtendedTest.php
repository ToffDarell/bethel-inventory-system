<?php

use Symfony\Component\Process\Process;

test('xmlwriter polyfill strips illegal xml control characters and preserves special characters under packaged php', function () {
    $packagedPhp = base_path('nativephp/electron/dist/win-unpacked/resources/build/php/php.exe');

    if (! file_exists($packagedPhp)) {
        $packagedPhp = base_path('vendor/nativephp/desktop/resources/build/php/php.exe');
    }

    expect(file_exists($packagedPhp))->toBeTrue('Packaged PHP binary must exist for verification');

    $scriptPath = base_path('scratch/test_xmlwriter_extended.php');
    expect(file_exists($scriptPath))->toBeTrue();

    $process = new Process([$packagedPhp, $scriptPath]);
    $process->run();

    $output = $process->getOutput();

    expect($process->isSuccessful())->toBeTrue("Packaged PHP failed: " . $process->getErrorOutput() . " " . $output);
    expect($output)->toContain('ALL EXTENDED XMLWRITER POLYFILL TESTS PASSED SUCCESSFULLY!');
    expect($output)->toContain('[PASS] A1 - Peso sign');
    expect($output)->toContain('[PASS] A2 - Apostrophe');
    expect($output)->toContain('[PASS] A3 - Ampersand');
    expect($output)->toContain('[PASS] A4 - Less-than and Greater-than');
    expect($output)->toContain('[PASS] A5 - Quotes');
    expect($output)->toContain('[PASS] A6 - Emoji');
    expect($output)->toContain('[PASS] A7 - Illegal XML control characters');
    expect($output)->toContain('[PASS] A8 - Leading and trailing spaces');
    expect($output)->toContain('[PASS] A9 - Tabs');
    expect($output)->toContain('[PASS] A10 - Line breaks');
    expect($output)->toContain('[PASS] A11 - 5,000-character string');
});
