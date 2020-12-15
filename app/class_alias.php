<?php

// Upstream tells us to use leafo/scssphp:~1.1, which doesn't exist.
// scssphp/scssphp:~1.1 does exist, but the classnames are different.

$oldClasses = [
  'Leafo\\ScssPhp\\Compiler',
  'Leafo\\ScssPhp\\Formatter\\Expanded',
  'Leafo\\ScssPhp\\Formatter\\Nested',
  'Leafo\\ScssPhp\\Formatter\\Compressed',
  'Leafo\\ScssPhp\\Formatter\\Crunched',
];
foreach ($oldClasses as $oldClass) {
  $newClass = str_replace('Leafo', 'ScssPhp', $oldClass);
  class_alias($newClass, $oldClass);
}
