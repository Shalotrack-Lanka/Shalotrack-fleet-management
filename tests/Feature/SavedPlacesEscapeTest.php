<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Regression: the saved-places map popup put the customer-typed place name into HTML unescaped
 * (a name like <img src=x onerror=...> ran script). The popup must go through escPlace().
 */
class SavedPlacesEscapeTest extends TestCase
{
    public function test_place_name_is_escaped_in_the_map_popup(): void
    {
        $src = file_get_contents(resource_path('views/saved-places/saved-places.blade.php'));

        $this->assertStringContainsString('function escPlace', $src);
        $this->assertStringContainsString('${escPlace(p.name)}', $src);
        $this->assertStringNotContainsString('>${p.name}<', $src);
        // 'use strict' must stay the first statement of the script
        $this->assertMatchesRegularExpression("/<script>\\s*'use strict';\\s*\\n\\s*\\/\\/ Place names/", $src);
    }
}