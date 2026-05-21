<?php

namespace App\Helper;

class FontAwesome
{
    public static function unicode(string $class): string
    {
        $hex = self::classToHex($class);
        if ($hex !== null) {
            return mb_chr(hexdec($hex), 'UTF-8');
        }
        return '';
    }

    public static function html(string $class, string $extraClasses = ''): string
    {
        $class = trim($class . ' ' . $extraClasses);
        return '<i class="' . e($class) . '"></i>';
    }

    public static function fontFamily(): string
    {
        return '"Font Awesome 6 Free"';
    }

    private static function classToHex(string $class): ?string
    {
        $class = trim(str_replace(['fas ', 'far ', 'fab ', 'fal '], '', $class));
        $map = [
            'fa-star' => 'f005', 'fa-cog' => 'f013', 'fa-heart' => 'f004',
            'fa-shield-alt' => 'f3ed', 'fa-bolt' => 'f0e7', 'fa-fire' => 'f06d',
            'fa-leaf' => 'f06c', 'fa-water' => 'f773', 'fa-sun' => 'f185',
            'fa-moon' => 'f186', 'fa-eye' => 'f06e', 'fa-brain' => 'f5dc',
            'fa-dna' => 'f471', 'fa-atom' => 'f5d2', 'fa-flask' => 'f0c3',
            'fa-skull' => 'f54c', 'fa-crown' => 'f521', 'fa-ghost' => 'f6e2',
            'fa-dragon' => 'f6d5', 'fa-fish' => 'f578', 'fa-spider' => 'f717',
            'fa-tree' => 'f1bb', 'fa-gem' => 'f3a5', 'fa-crosshairs' => 'f05b',
            'fa-bullseye' => 'f140', 'fa-paw' => 'f1b0', 'fa-feather' => 'f52d',
            'fa-wind' => 'f72e', 'fa-cloud' => 'f0c2', 'fa-snowflake' => 'f2dc',
            'fa-seedling' => 'f4d8', 'fa-rocket' => 'f135', 'fa-anchor' => 'f13d',
            'fa-compass' => 'f14e', 'fa-globe' => 'f0ac', 'fa-home' => 'f015',
            'fa-hammer' => 'f6e3', 'fa-tools' => 'f7d9', 'fa-wrench' => 'f0ad',
            'fa-lightbulb' => 'f0eb', 'fa-microchip' => 'f2db', 'fa-database' => 'f1c0',
            'fa-gamepad' => 'f11b', 'fa-trophy' => 'f091', 'fa-medal' => 'f5a2',
            'fa-gift' => 'f06b', 'fa-key' => 'f084', 'fa-lock' => 'f023',
            'fa-shield' => 'f132', 'fa-sword' => 'f71c', 'fa-magic' => 'f0d0',
            'fa-ring' => 'f70b', 'fa-clock' => 'f017', 'fa-bell' => 'f0f3',
            'fa-tag' => 'f02b', 'fa-flag' => 'f024', 'fa-search' => 'f002',
            'fa-filter' => 'f0b0', 'fa-chart-bar' => 'f080', 'fa-coins' => 'f51e',
            'fa-file' => 'f15b', 'fa-folder' => 'f07b', 'fa-trash' => 'f1f8',
            'fa-save' => 'f0c7', 'fa-play' => 'f04b', 'fa-pause' => 'f04c',
            'fa-stop' => 'f04d', 'fa-plus' => 'f067', 'fa-minus' => 'f068',
            'fa-times' => 'f00d', 'fa-check' => 'f00c', 'fa-exclamation' => 'f12a',
            'fa-question' => 'f128', 'fa-info' => 'f129', 'fa-ban' => 'f05e',
            'fa-circle' => 'f111', 'fa-square' => 'f0c8', 'fa-cube' => 'f1b2',
            'fa-cubes' => 'f1b3', 'fa-code' => 'f121', 'fa-bug' => 'f188',
            'fa-virus' => 'f804', 'fa-radiation' => 'f7b9', 'fa-biohazard' => 'f780',
            'fa-yin-yang' => 'f6ad', 'fa-cross' => 'f654', 'fa-mountain' => 'f6fc',
            'fa-icicles' => 'f7ad', 'fa-carrot' => 'f787', 'fa-egg' => 'f7fb',
            'fa-cookie' => 'f563', 'fa-coffee' => 'f0f4', 'fa-beer' => 'f0fc',
            'fa-wine-bottle' => 'f72f', 'fa-cocktail' => 'f561', 'fa-utensils' => 'f2e7',
            'fa-pizza-slice' => 'f818', 'fa-hamburger' => 'f805', 'fa-ice-cream' => 'f810',
            'fa-lemon' => 'f094', 'fa-apple-alt' => 'f5d1', 'fa-pepper-hot' => 'f816',
            'fa-pills' => 'f484', 'fa-syringe' => 'f48e', 'fa-stethoscope' => 'f0f1',
            'fa-tooth' => 'f5c9', 'fa-bone' => 'f5d7', 'fa-lungs' => 'f604',
            'fa-heartbeat' => 'f21e', 'fa-dumbbell' => 'f44b', 'fa-running' => 'f70c',
            'fa-swimmer' => 'f5c4', 'fa-bicycle' => 'f206', 'fa-car' => 'f1b9',
            'fa-ship' => 'f21a', 'fa-plane' => 'f072', 'fa-train' => 'f238',
            'fa-truck' => 'f0d1', 'fa-bus' => 'f207', 'fa-fighter-jet' => 'f0fb',
            'fa-helicopter' => 'f533', 'fa-map' => 'f279', 'fa-building' => 'f1ad',
            'fa-industry' => 'f275', 'fa-plug' => 'f1e6', 'fa-battery-full' => 'f240',
            'fa-memory' => 'f538', 'fa-server' => 'f233', 'fa-laptop' => 'f109',
            'fa-mobile-alt' => 'f3cd', 'fa-tablet-alt' => 'f3fa', 'fa-tv' => 'f26c',
            'fa-print' => 'f02f', 'fa-camera' => 'f030', 'fa-video' => 'f03d',
            'fa-headphones' => 'f025', 'fa-music' => 'f001', 'fa-guitar' => 'f7a6',
            'fa-drum' => 'f569', 'fa-microphone' => 'f130', 'fa-film' => 'f008',
            'fa-paint-brush' => 'f1fc', 'fa-palette' => 'f53f', 'fa-pencil-alt' => 'f303',
            'fa-book' => 'f02d', 'fa-book-open' => 'f518', 'fa-graduation-cap' => 'f19d',
            'fa-school' => 'f549', 'fa-university' => 'f19c', 'fa-chess' => 'f439',
            'fa-dice' => 'f522', 'fa-puzzle-piece' => 'f12e', 'fa-award' => 'f559',
            'fa-unlock' => 'f09c', 'fa-axe' => 'f6b2', 'fa-hat-wizard' => 'f6e8',
            'fa-hourglass' => 'f254', 'fa-calendar' => 'f133', 'fa-envelope' => 'f0e0',
            'fa-phone' => 'f095', 'fa-comment' => 'f075', 'fa-tags' => 'f02c',
            'fa-hashtag' => 'f292', 'fa-barcode' => 'f02a', 'fa-qrcode' => 'f029',
            'fa-fingerprint' => 'f577', 'fa-id-card' => 'f2c2', 'fa-passport' => 'f5ab',
            'fa-stamp' => 'f5bf', 'fa-thumbtack' => 'f08d', 'fa-map-pin' => 'f276',
            'fa-map-marker-alt' => 'f3c5', 'fa-location-arrow' => 'f124',
            'fa-street-view' => 'f21d', 'fa-binoculars' => 'f1e5', 'fa-sort' => 'f0dc',
            'fa-chart-line' => 'f201', 'fa-chart-pie' => 'f200', 'fa-balance-scale' => 'f24e',
            'fa-calculator' => 'f1ec', 'fa-percent' => 'f295', 'fa-dollar-sign' => 'f155',
            'fa-money-bill' => 'f0d6', 'fa-credit-card' => 'f09d', 'fa-wallet' => 'f555',
            'fa-piggy-bank' => 'f4d3', 'fa-receipt' => 'f543', 'fa-paperclip' => 'f0c6',
            'fa-download' => 'f019', 'fa-upload' => 'f093', 'fa-cloud-upload-alt' => 'f382',
            'fa-cloud-download-alt' => 'f381', 'fa-sync' => 'f021', 'fa-redo' => 'f01e',
            'fa-undo' => 'f0e2', 'fa-history' => 'f1da', 'fa-power-off' => 'f011',
            'fa-toggle-on' => 'f205', 'fa-toggle-off' => 'f204', 'fa-forward' => 'f04e',
            'fa-backward' => 'f04a', 'fa-step-forward' => 'f051', 'fa-step-backward' => 'f048',
            'fa-eject' => 'f052', 'fa-random' => 'f074', 'fa-expand' => 'f065',
            'fa-compress' => 'f066', 'fa-arrows-alt' => 'f0b2', 'fa-chevron-up' => 'f077',
            'fa-chevron-down' => 'f078', 'fa-chevron-left' => 'f053', 'fa-chevron-right' => 'f054',
            'fa-arrow-up' => 'f062', 'fa-arrow-down' => 'f063', 'fa-arrow-left' => 'f060',
            'fa-arrow-right' => 'f061', 'fa-shapes' => 'f61f', 'fa-layer-group' => 'f5fd',
            'fa-vector-square' => 'f5cb', 'fa-draw-polygon' => 'f5ee', 'fa-bezier-curve' => 'f55b',
            'fa-project-diagram' => 'f542', 'fa-sitemap' => 'f0e8', 'fa-stream' => 'f550',
            'fa-code-branch' => 'f126', 'fa-terminal' => 'f120', 'fa-skull-crossbones' => 'f714',
            'fa-peace' => 'f67c', 'fa-pray' => 'f683', 'fa-star-of-david' => 'f69a',
            'fa-hand-rock' => 'f255', 'fa-otter' => 'f700', 'fa-cat' => 'f6be',
            'fa-dog' => 'f6d3', 'fa-horse' => 'f6f0', 'fa-fan' => 'f863',
            'fa-cheese' => 'f7ef', 'fa-bread-slice' => 'f7ec', 'fa-candy-cane' => 'f786',
            'fa-mug-hot' => 'f7b6', 'fa-glass-whiskey' => 'f7a0', 'fa-wine-glass-alt' => 'f5ce',
            'fa-drumstick-bite' => 'f6d7', 'fa-apple-whole' => 'f5d1', 'fa-mortar-pestle' => 'f5a7',
            'fa-prescription-bottle' => 'f485', 'fa-capsules' => 'f46b', 'fa-band-aid' => 'f462',
            'fa-joint' => 'f595', 'fa-weight' => 'f496', 'fa-hiking' => 'f6ec',
            'fa-space-shuttle' => 'f197', 'fa-subway' => 'f239', 'fa-motorcycle' => 'f21c',
            'fa-taxi' => 'f1ba', 'fa-ambulance' => 'f0f9', 'fa-warehouse' => 'f494',
            'fa-screwdriver' => 'f54a', 'fa-scroll' => 'f70e', 'fa-chalkboard' => 'f51b',
            'fa-pen' => 'f304', 'fa-theater-masks' => 'f630',
        ];
        return $map[$class] ?? null;
    }
}
