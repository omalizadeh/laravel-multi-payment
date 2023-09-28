<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PSR2' => true,
    '@PHP81Migration' => true,
    '@PHP82Migration' => true,
    'align_multiline_comment' => [
        'comment_type' => 'phpdocs_only',
    ],
    'binary_operator_spaces' => [
        'default' => 'single_space',
        'operators' => [],
    ],
    'blank_line_after_opening_tag' => true,
    'blank_line_before_statement' => [
        'statements' => [
            'break',
            'continue',
            'declare',
            'return',
            'throw',
            'try',
        ],
    ],
    'blank_line_between_import_groups' => true,
    'cast_spaces' => [
        'space' => 'single',
    ],
    'class_attributes_separation' => [
        'elements' => [
            'method' => 'one',
            'trait_import' => 'none',
        ],
    ],
    'class_reference_name_casing' => true,
    'combine_consecutive_issets' => true,
    'compact_nullable_typehint' => true,
    'concat_space' => [
        'spacing' => 'none',
    ],
    'declare_equal_normalize' => [
        'space' => 'none',
    ],
    'fully_qualified_strict_types' => true,
    'function_typehint_space' => true,
    'heredoc_to_nowdoc' => true,
    'include' => true,
    'increment_style' => [
        'style' => 'post',
    ],
    'linebreak_after_opening_tag' => true,
    'lowercase_cast' => true,
    'lowercase_static_reference' => true,
    'magic_constant_casing' => true,
    'magic_method_casing' => true,
    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line',
    ],
    'native_function_casing' => true,
    'new_with_braces' => [
        'anonymous_class' => false,
        'named_class' => true,
    ],
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => [
        'tokens' => [
            'extra',
            'throw',
            'use',
        ],
    ],
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => [
        'use' => 'echo',
    ],
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_short_bool_cast' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_spaces_around_offset' => [
        'positions' => [
            'inside',
            'outside',
        ],
    ],
    'no_unneeded_control_parentheses' => [
        'statements' => [
            'break',
            'clone',
            'continue',
            'echo_print',
            'return',
            'switch_case',
            'yield',
        ],
    ],
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_trailing_comma_in_singleline' => [
        'elements' => [
            'arguments',
            'array_destructuring',
            'array',
            'group_import',
        ],
    ],
    'no_whitespace_in_blank_line' => true,
    'not_operator_with_successor_space' => true,
    'object_operator_without_whitespace' => true,
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
    ],
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => [
        'tags' => [
            'example',
            'id',
            'internal',
            'inheritdoc',
            'inheritdocs',
            'link',
            'source',
            'toc',
            'tutorial',
        ],
    ],
    'phpdoc_no_access' => true,
    'phpdoc_no_package' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_scalar' => [
        'types' => [
            'boolean',
            'callback',
            'double',
            'integer',
            'real',
            'str',
        ],
    ],
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => [
        'groups' => [
            'simple',
            'alias',
            'meta',
        ],
    ],
    'phpdoc_var_without_name' => true,
    'return_type_declaration' => [
        'space_before' => 'none',
    ],
    'single_blank_line_before_namespace' => true,
    'single_line_comment_style' => [
        'comment_types' => [
            'hash',
        ],
    ],
    'single_quote' => true,
    'single_space_around_construct' => [
        'constructs_contain_a_single_space' => [
            'yield_from',
        ],
        'constructs_preceded_by_a_single_space' => [
            'use_lambda',
        ],
        'constructs_followed_by_a_single_space' => [
            'abstract',
            'as',
            'attribute',
            'break',
            'case',
            'catch',
            'class',
            'clone',
            'comment',
            'const',
            'const_import',
            'continue',
            'do',
            'echo',
            'else',
            'elseif',
            'enum',
            'extends',
            'final',
            'finally',
            'for',
            'foreach',
            'function',
            'function_import',
            'global',
            'goto',
            'if',
            'implements',
            'include',
            'include_once',
            'instanceof',
            'insteadof',
            'interface',
            'match',
            'named_argument',
            'namespace',
            'new',
            'open_tag_with_echo',
            'php_doc',
            'php_open',
            'print',
            'private',
            'protected',
            'public',
            'readonly',
            'require',
            'require_once',
            'return',
            'static',
            'switch',
            'throw',
            'trait',
            'try',
            'type_colon',
            'use',
            'use_lambda',
            'use_trait',
            'var',
            'while',
            'yield',
            'yield_from',
        ],
    ],
    'space_after_semicolon' => [
        'remove_in_empty_for_expressions' => false,
    ],
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trim_array_spaces' => true,
    'trailing_comma_in_multiline' => [
        'after_heredoc' => true,
        'elements' => [
            'arguments',
            'arrays',
            'parameters',
        ],
    ],
    'unary_operator_spaces' => true,
    'whitespace_after_comma_in_array' => [
        'ensure_single_space' => true,
    ],
];

$finder = Finder::create()
    ->in([
        __DIR__.'/config',
        __DIR__.'/resources',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
