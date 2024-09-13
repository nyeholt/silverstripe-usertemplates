<?php

use SilverStripe\View\Parsers\ShortcodeParser;
use Symbiote\UserTemplates\UserTemplateShortcode;

ShortcodeParser::get('default')->register('render_template', [UserTemplateShortcode::class, 'render_template']);
