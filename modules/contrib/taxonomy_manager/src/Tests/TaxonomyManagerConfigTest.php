<?php

/**
 * @file
 * Definition of Drupal\taxonomy_manager\Tests\TaxonomyManagerConfigTest.
 */

namespace Drupal\taxonomy_manager\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the configuration form.
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerConfigTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('taxonomy_manager');

  /**
   * Tests configuration options of the taxonomy_manager module.
   */
  function testTaxonomyManagerConfiguration() {
    // Create a user with permission to administer taxonomy.
    $user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($user);

    // Make a POST request to admin/config/user-interface/taxonomy-manager-settings.
    $edit = array();
    $edit['taxonomy_manager_disable_mouseover'] = '1';
    $edit['taxonomy_manager_pager_tree_page_size'] = '50';
    $this->drupalPostForm('admin/config/user-interface/taxonomy-manager-settings', $edit, t('Save configuration'));
    $this->assertResponse(200);
    $this->assertText(t('The configuration options have been saved.'), "Saving configuration options successfully.");

  }
}
