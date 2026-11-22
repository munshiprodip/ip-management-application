<?php

declare(strict_types=1);

namespace App\Livewire\Requests;

use App\Actions\Workflows\ApproveRequestAction;
use App\Actions\Workflows\CancelRequestAction;
use App\Actions\Workflows\ProvisionRequestAction;
use App\Actions\Workflows\RejectRequestAction;
use App\Actions\Workflows\SubmitRequestAction;
use App\Domain\Workflows\Enums\RequestPriority;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Domain\Workflows\Enums\RequestType;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\IpRequest;
use App\Models\Subnet;
use App\Services\Workflows\ApprovalWorkflowService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('IP Requests & Approvals - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $activeTab = 'all'; // all, pending_my_approval, my_requests, pending, approved, provisioned, rejected

    #[Url]
    public string $selectedType = '';

    #[Url]
    public string $selectedPriority = '';

    #[Url]
    public string $selectedDepartment = '';

    // Review Slide-over Drawer
    public ?int $selectedRequestId = null;
    public string $approvalComments = '';
    public string $rejectionReason = '';
    public string $cancellationReason = '';
    public bool $showRejectModal = false;
    public bool $showCancelModal = false;

    // Submit Request Modal
    public bool $showCreateModal = false;
    public string $formRequestType = 'ip_assignment';
    public string $formDepartmentId = '';
    public string $formPriority = 'normal';
    public string $formReason = '';
    public string $formSubnetId = '';
    public string $formIpAddress = '';
    public string $formDeviceId = '';
    public string $formInterfaceId = '';
    public string $formHostname = '';
    public string $formClassification = 'static_host';
    public int $formQuarantineDays = 14;
    public string $formTargetInterfaceId = '';
    public ?int $formIpAddressId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
    }

    public function openReviewDrawer(int $requestId): void
    {
        $this->selectedRequestId = $requestId;
        $this->approvalComments = '';
        $this->rejectionReason = '';
        $this->cancellationReason = '';
        $this->showRejectModal = false;
        $this->showCancelModal = false;
    }

    public function closeReviewDrawer(): void
    {
        $this->selectedRequestId = null;
    }

    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function resetCreateForm(): void
    {
        $this->formRequestType = 'ip_assignment';
        $this->formDepartmentId = '';
        $this->formPriority = 'normal';
        $this->formReason = '';
        $this->formSubnetId = '';
        $this->formIpAddress = '';
        $this->formDeviceId = '';
        $this->formInterfaceId = '';
        $this->formHostname = '';
        $this->formClassification = 'static_host';
        $this->formQuarantineDays = 14;
        $this->formTargetInterfaceId = '';
        $this->formIpAddressId = null;
        $this->resetErrorBag();
    }

    public function submitRequest(SubmitRequestAction $action): void
    {
        $this->validate([
            'formRequestType' => 'required|string',
            'formPriority' => 'required|string',
            'formReason' => 'required|string|min:5|max:1000',
            'formDepartmentId' => 'nullable|integer',
        ]);

        $payload = [
            'hostname' => $this->formHostname,
            'classification' => $this->formClassification,
        ];

        if ($this->formRequestType === 'ip_assignment') {
            $this->validate([
                'formSubnetId' => 'required|integer|exists:subnets,id',
            ]);
            $payload['subnet_id'] = (int) $this->formSubnetId;
            $payload['ip_address'] = $this->formIpAddress ?: null;
            $payload['device_id'] = $this->formDeviceId ? (int) $this->formDeviceId : null;
            $payload['interface_id'] = $this->formInterfaceId ? (int) $this->formInterfaceId : null;
        } elseif ($this->formRequestType === 'ip_release') {
            $this->validate([
                'formIpAddressId' => 'required|integer|exists:ip_addresses,id',
                'formQuarantineDays' => 'required|integer|min:0|max:90',
            ]);
            $payload['ip_address_id'] = (int) $this->formIpAddressId;
            $payload['quarantine_days'] = $this->formQuarantineDays;
        } elseif ($this->formRequestType === 'ip_transfer') {
            $this->validate([
                'formIpAddressId' => 'required|integer|exists:ip_addresses,id',
                'formTargetInterfaceId' => 'required|integer|exists:device_interfaces,id',
            ]);
            $payload['ip_address_id'] = (int) $this->formIpAddressId;
            $payload['target_interface_id'] = (int) $this->formTargetInterfaceId;
        }

        try {
            $request = $action->execute(Auth::user(), [
                'request_type' => $this->formRequestType,
                'department_id' => $this->formDepartmentId ? (int) $this->formDepartmentId : null,
                'priority' => $this->formPriority,
                'payload' => $payload,
                'reason' => $this->formReason,
            ]);

            session()->flash('success', "Request {$request->request_number} submitted successfully.");
            $this->closeCreateModal();
            $this->openReviewDrawer($request->id);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Error submitting request: ' . $e->getMessage());
        }
    }

    public function approve(int $requestId, ApproveRequestAction $action): void
    {
        $request = IpRequest::findOrFail($requestId);

        try {
            $action->execute($request, Auth::user(), $this->approvalComments ?: null);
            session()->flash('success', "Request {$request->request_number} approved successfully.");
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    public function reject(int $requestId, RejectRequestAction $action): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:5|max:500',
        ]);

        $request = IpRequest::findOrFail($requestId);

        try {
            $action->execute($request, Auth::user(), $this->rejectionReason);
            session()->flash('success', "Request {$request->request_number} rejected.");
            $this->showRejectModal = false;
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    public function cancel(int $requestId, CancelRequestAction $action): void
    {
        $this->validate([
            'cancellationReason' => 'required|string|min:5|max:500',
        ]);

        $request = IpRequest::findOrFail($requestId);

        try {
            $action->execute($request, Auth::user(), $this->cancellationReason);
            session()->flash('success', "Request {$request->request_number} cancelled.");
            $this->showCancelModal = false;
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Cancellation failed: ' . $e->getMessage());
        }
    }

    public function provision(int $requestId, ProvisionRequestAction $action): void
    {
        $request = IpRequest::findOrFail($requestId);

        try {
            $action->execute($request, Auth::user());
            session()->flash('success', "Request {$request->request_number} provisioned successfully.");
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Provisioning failed: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        $user = Auth::user();

        // Base scoped query (T-021)
        $baseQuery = IpRequest::query()->forScope($user);

        // Compute KPIs
        $totalCount = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->whereIn('status', [RequestStatus::SUBMITTED->value, RequestStatus::PENDING_APPROVAL->value])->count();
        $approvedCount = (clone $baseQuery)->where('status', RequestStatus::APPROVED->value)->count();
        $provisionedCount = (clone $baseQuery)->where('status', RequestStatus::PROVISIONED->value)->count();

        // Pending My Approval count: user must be eligible to approve AND NOT be the requester (T-022)
        $pendingMyApprovalCount = 0;
        if ($user->isNetworkAdmin() || $user->isApprover()) {
            $pendingMyApprovalCount = (clone $baseQuery)
                ->whereIn('status', [RequestStatus::SUBMITTED->value, RequestStatus::PENDING_APPROVAL->value])
                ->where('requester_id', '!=', $user->id)
                ->count();
        }

        $overdueCount = (clone $baseQuery)
            ->whereIn('status', [RequestStatus::SUBMITTED->value, RequestStatus::PENDING_APPROVAL->value])
            ->where('sla_due_at', '<', now())
            ->count();

        // Filter by tab
        $query = (clone $baseQuery)->with(['requester', 'department', 'provisioner', 'approvals.approver']);

        if ($this->activeTab === 'pending_my_approval') {
            $query->whereIn('status', [RequestStatus::SUBMITTED->value, RequestStatus::PENDING_APPROVAL->value])
                ->where('requester_id', '!=', $user->id);
        } elseif ($this->activeTab === 'my_requests') {
            $query->where('requester_id', $user->id);
        } elseif ($this->activeTab === 'pending') {
            $query->whereIn('status', [RequestStatus::SUBMITTED->value, RequestStatus::PENDING_APPROVAL->value]);
        } elseif ($this->activeTab === 'approved') {
            $query->where('status', RequestStatus::APPROVED->value);
        } elseif ($this->activeTab === 'provisioned') {
            $query->where('status', RequestStatus::PROVISIONED->value);
        } elseif ($this->activeTab === 'rejected') {
            $query->where('status', RequestStatus::REJECTED->value);
        }

        // Apply search
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('request_number', 'like', '%' . $this->search . '%')
                    ->orWhere('reason', 'like', '%' . $this->search . '%')
                    ->orWhereHas('requester', function ($uq) {
                        $uq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->selectedType !== '') {
            $query->where('request_type', $this->selectedType);
        }

        if ($this->selectedPriority !== '') {
            $query->where('priority', $this->selectedPriority);
        }

        if ($this->selectedDepartment !== '') {
            $query->where('department_id', (int) $this->selectedDepartment);
        }

        $requests = $query->orderByDesc('created_at')->paginate(15);

        // Lookup selected request for drawer
        $selectedRequest = null;
        if ($this->selectedRequestId !== null) {
            $selectedRequest = IpRequest::with(['requester', 'department', 'approvals.approver', 'changeRecords', 'provisioner'])
                ->find($this->selectedRequestId);
        }

        $departments = Department::orderBy('name')->get();
        $subnets = Subnet::where('subnet_type', 'assignable')->with('vrf')->orderBy('cidr')->get();
        $devices = Device::with('interfaces')->orderBy('name')->get();
        $assignedIps = IpAddress::where('allocation_status', 'assigned')->with('subnet')->orderBy('address')->get();

        return view('livewire.requests.index', [
            'requests' => $requests,
            'selectedRequest' => $selectedRequest,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'pendingMyApprovalCount' => $pendingMyApprovalCount,
            'approvedCount' => $approvedCount,
            'provisionedCount' => $provisionedCount,
            'overdueCount' => $overdueCount,
            'departments' => $departments,
            'subnets' => $subnets,
            'devices' => $devices,
            'assignedIps' => $assignedIps,
            'workflowService' => app(ApprovalWorkflowService::class),
        ]);
    }
}
