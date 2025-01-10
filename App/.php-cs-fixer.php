<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__
    ])
    ->exclude('vendor');

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR12' => true,  // Включаем все правила стандарта PSR-12
        'array_syntax' => ['syntax' => 'short'],  // Использовать короткий синтаксис массивов: [] вместо array()
        'ordered_imports' => ['sort_algorithm' => 'alpha'],  // Сортировать use-импорты по алфавиту
        'no_unused_imports' => true,  // Удалять неиспользуемые импорты
        'trailing_comma_in_multiline' => true,  // Добавлять запятую после последнего элемента в многострочных массивах
        'single_quote' => true,  // Использовать одинарные кавычки вместо двойных где возможно
        'no_empty_comment' => true,  // Удалять пустые комментарии
        'no_extra_blank_lines' => true,  // Удалять лишние пустые строки
        'phpdoc_trim' => true,  // Удалять пустые строки в начале и конце PHPDoc
        'binary_operator_spaces' => true,  // Правильные пробелы вокруг операторов
        'cast_spaces' => true,  // Пробелы после приведения типов
        'concat_space' => ['spacing' => 'one'],  // Один пробел до и после оператора конкатенации
        'include' => true,  // Правильное форматирование include/require
        'new_with_braces' => true,  // Обязательные скобки после new
        'no_empty_statement' => true,  // Удалять пустые операторы
        'no_leading_import_slash' => true,  // Удалять начальный слеш в use-импортах
        'no_leading_namespace_whitespace' => true,  // Удалять пробелы перед namespace
        'no_multiline_whitespace_around_double_arrow' => true,  // Форматирование стрелок в массивах
        'multiline_whitespace_before_semicolons' => true,  // Форматирование точки с запятой в многострочных выражениях
        'no_singleline_whitespace_before_semicolons' => true,  // Удалять пробелы перед точкой с запятой
        'no_trailing_comma_in_singleline' => true,  // Удалять запятую в конце однострочных конструкций
        'object_operator_without_whitespace' => true,  // Убирать пробелы вокруг ->
        'single_line_after_imports' => true,  // Одна пустая строка после импортов
        'ternary_operator_spaces' => true,  // Пробелы вокруг тернарного оператора
        'trim_array_spaces' => true,  // Удалять лишние пробелы в массивах
        'unary_operator_spaces' => true,  // Форматирование унарных операторов
        'visibility_required' => true,  // Обязательное указание видимости методов и свойств
    ])
    ->setFinder($finder);
