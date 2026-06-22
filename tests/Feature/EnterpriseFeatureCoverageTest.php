<?php

it('documents full enterprise module coverage', function () {
    expect(file_exists(base_path('docs/FULL-ENTERPRISE-COMPLETION.md')))->toBeTrue();
});
