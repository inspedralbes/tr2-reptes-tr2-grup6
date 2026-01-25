<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache Flushed";
} else {
    echo "Opcache not available";
}
