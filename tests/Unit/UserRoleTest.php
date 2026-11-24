<?php

namespace Tests\Unit;

use App\Domain\Security\Enums\UserRole;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_user_roles_have_accurate_labels(): void
    {
        $this->assertEquals('Super Admin', UserRole::SUPER_ADMIN->label());
        $this->assertEquals('Admin', UserRole::ADMIN->label());
        $this->assertEquals('User', UserRole::USER->label());
        $this->assertEquals('Admin', UserRole::NETWORK_ADMIN->label());
        $this->assertEquals('User', UserRole::OPERATOR->label());
    }

    public function test_user_role_methods_evaluate_correctly(): void
    {
        $superAdmin = new User(['role' => UserRole::SUPER_ADMIN, 'status' => 'active']);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($superAdmin->isNetworkAdmin());
        $this->assertTrue($superAdmin->isOperator());
        $this->assertTrue($superAdmin->canManageRoles());
        $this->assertTrue($superAdmin->canManageUsers());
        $this->assertTrue($superAdmin->canManageNetwork());

        $admin = new User(['role' => UserRole::ADMIN, 'status' => 'active']);
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->canManageRoles());
        $this->assertTrue($admin->canManageUsers());
        $this->assertTrue($admin->canManageNetwork());

        $user = new User(['role' => UserRole::USER, 'status' => 'active']);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->canManageRoles());
        $this->assertFalse($user->canManageUsers());
        $this->assertTrue($user->canManageNetwork());

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
        $this->assertFalse($inactiveAdmin->canManageUsers());
        $this->assertFalse($inactiveAdmin->canManageRoles());
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

