<?php

namespace Tests\Unit;

use App\Domain\Security\Enums\UserRole;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_user_roles_have_accurate_labels(): void
    {
        $this->assertEquals('Platform Super Admin', UserRole::SUPER_ADMIN->label());
        $this->assertEquals('Network Admin', UserRole::NETWORK_ADMIN->label());
        $this->assertEquals('IP Operator', UserRole::OPERATOR->label());
        $this->assertEquals('Department Operator', UserRole::DEPARTMENT_OPERATOR->label());
        $this->assertEquals('Auditor', UserRole::AUDITOR->label());
        $this->assertEquals('Viewer', UserRole::VIEWER->label());
        $this->assertEquals('Service Agent', UserRole::AGENT->label());
        $this->assertEquals('Independent Approver', UserRole::APPROVER->label());
    }

    public function test_user_role_methods_evaluate_correctly(): void
    {
        $superAdmin = new User(['role' => UserRole::SUPER_ADMIN, 'status' => 'active']);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isNetworkAdmin());
        $this->assertTrue($superAdmin->isOperator());
        $this->assertTrue($superAdmin->canManageNetwork());

        $netAdmin = new User(['role' => UserRole::NETWORK_ADMIN, 'status' => 'active']);
        $this->assertFalse($netAdmin->isSuperAdmin());
        $this->assertTrue($netAdmin->isNetworkAdmin());
        $this->assertTrue($netAdmin->isOperator());
        $this->assertTrue($netAdmin->canManageNetwork());

        $operator = new User(['role' => UserRole::OPERATOR, 'status' => 'active']);
        $this->assertFalse($operator->isNetworkAdmin());
        $this->assertTrue($operator->isOperator());
        $this->assertFalse($operator->canManageNetwork());

        $viewer = new User(['role' => UserRole::VIEWER, 'status' => 'active']);
        $this->assertFalse($viewer->isOperator());
        $this->assertFalse($viewer->canManageNetwork());

        $inactiveAdmin = new User(['role' => UserRole::SUPER_ADMIN, 'status' => 'inactive']);
        $this->assertFalse($inactiveAdmin->canManageNetwork());
    }

    public function test_has_role_supports_single_and_array_arguments(): void
    {
        $user = new User(['role' => UserRole::OPERATOR]);

        $this->assertTrue($user->hasRole(UserRole::OPERATOR));
        $this->assertTrue($user->hasRole('operator'));
        $this->assertTrue($user->hasRole([UserRole::NETWORK_ADMIN, UserRole::OPERATOR]));
        $this->assertFalse($user->hasRole([UserRole::SUPER_ADMIN, UserRole::VIEWER]));
    }
}

