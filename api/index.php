<?php

/*
 * Vercel entry point (vercel-php runtime). vercel.json routes every non-static request here.
 * The deployed filesystem is read-only, so bootstrap/app.php moves storage to /tmp on Vercel.
 */
require __DIR__.'/../public/index.php';
