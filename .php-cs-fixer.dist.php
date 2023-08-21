<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
;

$rules = [
    // Rules that follow PSR-12 standard.
    '@PSR12' => true,

    // Rules that follow PSR-12 standard. This set contains rules that are risky.
    '@PSR12:risky' => true,

    // Ignore function arguments lists that contain newlines (it for more readable attributes set right in constructors).
    'method_argument_space' => ['on_multiline' => 'ignore'],

    // PHP arrays should be declared using the short syntax.
    'array_syntax' => ['syntax' => 'short'],

    // Each line of multi-line DocComments must have an asterisk [PSR-5] and must be aligned with the first one.
    'align_multiline_comment' => true,

    // A single space or none should be between cast and variable.
    'cast_spaces' => true,

    // Class, trait and interface elements must be separated with one or none blank line.
    'class_attributes_separation' => ['elements' => ['method' => 'one']],

    // There should not be any empty comments.
    'no_empty_comment' => true,

    // Unused use statements must be removed.
    'no_unused_imports' => true,

    // Scalar types should always be written in the same form. int not integer, bool not boolean, float not real or double.
    'phpdoc_scalar' => true,

    // Single line @var PHPDoc should have proper spacing.
    'phpdoc_single_line_var_spacing' => true,

    // Removes extra blank lines after summary and after description in PHPDoc.
    'phpdoc_trim' => true,

    // @var and @type annotations must have type and name in the correct order.
    'phpdoc_var_annotation_correct_order' => true,

    // Remove useless (semicolon) statements.
    'no_empty_statement' => true,

    // There MUST NOT be spaces around offset braces.
    'no_spaces_around_offset' => true,

    // Force strict types declaration in all files.
    'declare_strict_types' => true,

    // Comparisons should be strict.
    'strict_comparison' => true,

    // Ordering use statements
    'ordered_imports' => true,

    // Replace get_class calls on object variables with class keyword syntax.
    'get_class_to_class_keyword' => true,

    // Class DateTimeImmutable should be used instead of DateTime.
    'date_time_immutable' => true,

    // Removes @param, @return and @var tags that don’t provide any useful information.
    'no_superfluous_phpdoc_tags' => true,

    // Multi-line arrays, arguments list, parameters list and match expressions must have a trailing comma.
    'trailing_comma_in_multiline' => [
        'after_heredoc' => true,
        'elements' => ['arrays', 'match', 'arguments', 'parameters'],
    ],
];

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
;
