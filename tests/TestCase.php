<?php namespace Volicon\Acl;

/**
 * Description of TestCase
 *
 * @author nadav.v
 */
class TestCase extends \Orchestra\Testbench\TestCase {
	
	protected function getPackageAliases()
    {
        return array(
            'Acl' => 'Volicon\Acl\Facade\Acl'
        );
    }
	
	protected function getPackageProviders()
    {
        return array('Volicon\Acl\AclServiceProvider');
    }
	
	public function setUp()
    {
        parent::setUp();
    }
}
