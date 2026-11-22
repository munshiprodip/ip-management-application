<?php

declare(strict_types=1);

namespace App\Livewire\Devices;

use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Actions\DeviceInventory\CreateInterfaceAction;
use App\Actions\DeviceInventory\DeleteDeviceAction;
use App\Actions\DeviceInventory\DeleteInterfaceAction;
use App\Actions\DeviceInventory\UpdateDeviceAction;
use App\Actions\DeviceInventory\UpdateInterfaceAction;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\DeviceInventory\Enums\DeviceCriticality;
use App\Domain\DeviceInventory\Enums\DeviceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceStatus;
use App\Domain\DeviceInventory\Enums\InterfaceType;
use App\Domain\DeviceInventory\ValueObjects\MacAddress;
use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Site;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Device & Interface Inventory - KYAMCH IPAM')]
class Index extends Component
{
    use WithPagination;

    // Filters & Search
    public string $search = '';
    public string $siteFilter = 'all';
    public string $departmentFilter = 'all';
    public string $categoryFilter = 'all';
    public string $statusFilter = 'all';
    public string $criticalityFilter = 'all';

    // Device Create / Edit Modal State
    public bool $showDeviceModal = false;
    public bool $isEditingDevice = false;
    public ?int $selectedDeviceId = null;

    // Device Form Inputs
    public string $device_code = '';
    public string $device_name = '';
    public string $device_hostname = '';
    public ?int $device_type_id = null;
    public ?int $device_manufacturer_id = null;
    public ?int $device_model_id = null;
    public string $device_asset_tag = '';
    public string $device_serial_number = '';
    public ?int $device_site_id = null;
    public ?int $device_department_id = null;
    public ?int $device_location_id = null;
    public ?int $device_owner_user_id = null;
    public string $device_rack_location = '';
    public string $device_criticality = 'medium';
    public string $device_status = 'active';
    public string $device_environment = 'production';
    public string $device_os_firmware = '';
    public bool $device_is_monitored = true;
    public string $device_notes = '';

    // Optional Initial Interface Form
    public string $initial_if_name = '';
    public string $initial_if_mac = '';
    public string $initial_if_type = 'physical';

    // Detail Slide-Over State
    public bool $showDetailSlideover = false;
    public ?int $viewDeviceId = null;
    public string $detailTab = 'interfaces'; // 'interfaces', 'specs', 'audit'

    // Interface Add / Edit Modal State
    public bool $showInterfaceModal = false;
    public bool $isEditingInterface = false;
    public ?int $selectedInterfaceId = null;

    // Interface Form Inputs
    public string $if_name = '';
    public string $if_type = 'physical';
    public string $if_mac = '';
    public ?int $if_vlan_id = null;
    public ?int $if_speed_mbps = null;
    public string $if_status = 'up';
    public string $if_switch_port = '';
    public string $if_description = '';

    // Delete Modal State
    public bool $showDeleteDeviceModal = false;
    public bool $showDeleteInterfaceModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSiteFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCriticalityFilter(): void
    {
        $this->resetPage();
    }

    // --- Device Actions ---

    public function openCreateDeviceModal(): void
    {
        Gate::authorize('create', Device::class);

        $this->resetDeviceForm();
        $this->isEditingDevice = false;
        $this->showDeviceModal = true;
    }

    public function editDevice(int $id): void
    {
        $device = Device::findOrFail($id);
        Gate::authorize('update', $device);

        $this->selectedDeviceId = $device->id;
        $this->isEditingDevice = true;

        $this->device_code = $device->code;
        $this->device_name = $device->name;
        $this->device_hostname = $device->hostname ?? '';
        $this->device_type_id = $device->device_type_id;
        $this->device_manufacturer_id = $device->manufacturer_id;
        $this->device_model_id = $device->device_model_id;
        $this->device_asset_tag = $device->asset_tag ?? '';
        $this->device_serial_number = $device->serial_number ?? '';
        $this->device_site_id = $device->site_id;
        $this->device_department_id = $device->department_id;
        $this->device_location_id = $device->location_id;
        $this->device_owner_user_id = $device->owner_user_id;
        $this->device_rack_location = $device->rack_location ?? '';
        $this->device_criticality = $device->criticality->value;
        $this->device_status = $device->status->value;
        $this->device_environment = $device->environment;
        $this->device_os_firmware = $device->os_firmware ?? '';
        $this->device_is_monitored = $device->is_monitored;
        $this->device_notes = $device->notes ?? '';

        $this->showDeviceModal = true;
    }

    public function saveDevice(CreateDeviceAction $createAction, UpdateDeviceAction $updateAction): void
    {
        $this->validate([
            'device_code' => 'required|string|max:64',
            'device_name' => 'required|string|max:255',
            'device_type_id' => 'required|exists:device_types,id',
            'device_site_id' => 'required|exists:sites,id',
            'device_criticality' => 'required|string',
            'device_status' => 'required|string',
        ]);

        try {
            if ($this->isEditingDevice && $this->selectedDeviceId) {
                $device = Device::findOrFail($this->selectedDeviceId);
                Gate::authorize('update', $device);

                $updateAction->execute($device, [
                    'code' => $this->device_code,
                    'name' => $this->device_name,
                    'hostname' => !empty($this->device_hostname) ? $this->device_hostname : null,
                    'device_type_id' => $this->device_type_id,
                    'manufacturer_id' => $this->device_manufacturer_id,
                    'device_model_id' => $this->device_model_id,
                    'asset_tag' => !empty($this->device_asset_tag) ? $this->device_asset_tag : null,
                    'serial_number' => !empty($this->device_serial_number) ? $this->device_serial_number : null,
                    'site_id' => $this->device_site_id,
                    'department_id' => $this->device_department_id,
                    'location_id' => $this->device_location_id,
                    'owner_user_id' => $this->device_owner_user_id,
                    'rack_location' => !empty($this->device_rack_location) ? $this->device_rack_location : null,
                    'criticality' => DeviceCriticality::from($this->device_criticality),
                    'status' => DeviceStatus::from($this->device_status),
                    'environment' => $this->device_environment,
                    'os_firmware' => !empty($this->device_os_firmware) ? $this->device_os_firmware : null,
                    'is_monitored' => $this->device_is_monitored,
                    'notes' => !empty($this->device_notes) ? $this->device_notes : null,
                ]);

                session()->flash('success', "Device '{$device->name}' updated successfully.");
            } else {
                Gate::authorize('create', Device::class);

                $device = $createAction->execute([
                    'code' => $this->device_code,
                    'name' => $this->device_name,
                    'hostname' => !empty($this->device_hostname) ? $this->device_hostname : null,
                    'device_type_id' => $this->device_type_id,
                    'manufacturer_id' => $this->device_manufacturer_id,
                    'device_model_id' => $this->device_model_id,
                    'asset_tag' => !empty($this->device_asset_tag) ? $this->device_asset_tag : null,
                    'serial_number' => !empty($this->device_serial_number) ? $this->device_serial_number : null,
                    'site_id' => $this->device_site_id,
                    'department_id' => $this->device_department_id,
                    'location_id' => $this->device_location_id,
                    'owner_user_id' => $this->device_owner_user_id,
                    'rack_location' => !empty($this->device_rack_location) ? $this->device_rack_location : null,
                    'criticality' => DeviceCriticality::from($this->device_criticality),
                    'status' => DeviceStatus::from($this->device_status),
                    'environment' => $this->device_environment,
                    'os_firmware' => !empty($this->device_os_firmware) ? $this->device_os_firmware : null,
                    'is_monitored' => $this->device_is_monitored,
                    'notes' => !empty($this->device_notes) ? $this->device_notes : null,
                    'default_interface_name' => !empty($this->initial_if_name) ? $this->initial_if_name : null,
                    'default_interface_mac' => !empty($this->initial_if_mac) ? $this->initial_if_mac : null,
                    'default_interface_type' => InterfaceType::from($this->initial_if_type),
                ]);

                session()->flash('success', "Device '{$device->name}' registered successfully.");
            }

            $this->showDeviceModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function confirmDeleteDevice(int $id): void
    {
        $device = Device::findOrFail($id);
        Gate::authorize('delete', $device);

        $this->selectedDeviceId = $device->id;
        $this->showDeleteDeviceModal = true;
    }

    public function deleteDevice(DeleteDeviceAction $action): void
    {
        $device = Device::findOrFail($this->selectedDeviceId);
        Gate::authorize('delete', $device);

        try {
            $action->execute($device);
            session()->flash('success', "Device '{$device->name}' decommissioned and removed.");
            $this->showDetailSlideover = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showDeleteDeviceModal = false;
    }

    // --- Detail Slide-Over ---

    public function viewDevice(int $id): void
    {
        $this->viewDeviceId = $id;
        $this->detailTab = 'interfaces';
        $this->showDetailSlideover = true;
    }

    public function closeDetailSlideover(): void
    {
        $this->showDetailSlideover = false;
        $this->viewDeviceId = null;
    }

    // --- Interface Actions ---

    public function openAddInterfaceModal(): void
    {
        Gate::authorize('create', DeviceInterface::class);

        $this->resetInterfaceForm();
        $this->isEditingInterface = false;
        $this->showInterfaceModal = true;
    }

    public function editInterface(int $id): void
    {
        $interface = DeviceInterface::findOrFail($id);
        Gate::authorize('update', $interface);

        $this->selectedInterfaceId = $interface->id;
        $this->isEditingInterface = true;

        $this->if_name = $interface->name;
        $this->if_type = $interface->type->value;
        $this->if_mac = $interface->mac_address ?? '';
        $this->if_vlan_id = $interface->vlan_id;
        $this->if_speed_mbps = $interface->speed_mbps;
        $this->if_status = $interface->status->value;
        $this->if_switch_port = $interface->switch_port_label ?? '';
        $this->if_description = $interface->description ?? '';

        $this->showInterfaceModal = true;
    }

    public function saveInterface(CreateInterfaceAction $createAction, UpdateInterfaceAction $updateAction): void
    {
        $this->validate([
            'if_name' => 'required|string|max:64',
            'if_type' => 'required|string',
            'if_status' => 'required|string',
        ]);

        if (!empty($this->if_mac)) {
            try {
                MacAddress::parse($this->if_mac);
            } catch (\Exception $e) {
                $this->addError('if_mac', $e->getMessage());
                return;
            }
        }

        try {
            if ($this->isEditingInterface && $this->selectedInterfaceId) {
                $interface = DeviceInterface::findOrFail($this->selectedInterfaceId);
                Gate::authorize('update', $interface);

                $updateAction->execute($interface, [
                    'name' => $this->if_name,
                    'type' => InterfaceType::from($this->if_type),
                    'mac_address' => !empty($this->if_mac) ? $this->if_mac : null,
                    'vlan_id' => $this->if_vlan_id,
                    'speed_mbps' => $this->if_speed_mbps,
                    'status' => InterfaceStatus::from($this->if_status),
                    'switch_port_label' => !empty($this->if_switch_port) ? $this->if_switch_port : null,
                    'description' => !empty($this->if_description) ? $this->if_description : null,
                ]);

                session()->flash('success', "Interface '{$interface->name}' updated.");
            } else {
                Gate::authorize('create', DeviceInterface::class);
                $device = Device::findOrFail($this->viewDeviceId);

                $interface = $createAction->execute($device, [
                    'name' => $this->if_name,
                    'type' => InterfaceType::from($this->if_type),
                    'mac_address' => !empty($this->if_mac) ? $this->if_mac : null,
                    'vlan_id' => $this->if_vlan_id,
                    'speed_mbps' => $this->if_speed_mbps,
                    'status' => InterfaceStatus::from($this->if_status),
                    'switch_port_label' => !empty($this->if_switch_port) ? $this->if_switch_port : null,
                    'description' => !empty($this->if_description) ? $this->if_description : null,
                ]);

                session()->flash('success', "Interface '{$interface->name}' added to device.");
            }

            $this->showInterfaceModal = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function confirmDeleteInterface(int $id): void
    {
        $interface = DeviceInterface::findOrFail($id);
        Gate::authorize('delete', $interface);

        $this->selectedInterfaceId = $interface->id;
        $this->showDeleteInterfaceModal = true;
    }

    public function deleteInterface(DeleteInterfaceAction $action): void
    {
        $interface = DeviceInterface::findOrFail($this->selectedInterfaceId);
        Gate::authorize('delete', $interface);

        try {
            $action->execute($interface);
            session()->flash('success', "Interface '{$interface->name}' deleted.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showDeleteInterfaceModal = false;
    }

    // --- Helpers ---

    private function resetDeviceForm(): void
    {
        $this->selectedDeviceId = null;
        $this->device_code = '';
        $this->device_name = '';
        $this->device_hostname = '';
        $this->device_type_id = DeviceType::first()?->id;
        $this->device_manufacturer_id = Manufacturer::first()?->id;
        $this->device_model_id = null;
        $this->device_asset_tag = '';
        $this->device_serial_number = '';
        $this->device_site_id = Site::first()?->id;
        $this->device_department_id = Department::first()?->id;
        $this->device_location_id = null;
        $this->device_owner_user_id = null;
        $this->device_rack_location = '';
        $this->device_criticality = 'medium';
        $this->device_status = 'active';
        $this->device_environment = 'production';
        $this->device_os_firmware = '';
        $this->device_is_monitored = true;
        $this->device_notes = '';
        $this->initial_if_name = 'eth0';
        $this->initial_if_mac = '';
        $this->initial_if_type = 'physical';
        $this->resetErrorBag();
    }

    private function resetInterfaceForm(): void
    {
        $this->selectedInterfaceId = null;
        $this->if_name = '';
        $this->if_type = 'physical';
        $this->if_mac = '';
        $this->if_vlan_id = null;
        $this->if_speed_mbps = 1000;
        $this->if_status = 'up';
        $this->if_switch_port = '';
        $this->if_description = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Device::with(['deviceType', 'manufacturer', 'deviceModel', 'site', 'department', 'interfaces'])
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('hostname', 'like', $term)
                        ->orWhere('asset_tag', 'like', $term)
                        ->orWhere('serial_number', 'like', $term)
                        ->orWhereHas('interfaces', function ($ifQ) use ($term) {
                            $ifQ->where('mac_address', 'like', $term)
                                ->orWhere('switch_port_label', 'like', $term);
                        });
                });
            })
            ->when($this->siteFilter !== 'all', fn ($q) => $q->where('site_id', $this->siteFilter))
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->criticalityFilter !== 'all', fn ($q) => $q->where('criticality', $this->criticalityFilter))
            ->when($this->categoryFilter !== 'all', function ($q) {
                $q->whereHas('deviceType', fn ($dt) => $dt->where('category', $this->categoryFilter));
            });

        $devices = $query->orderBy('name')->paginate(12);

        // KPI Counts
        $kpi = [
            'total' => Device::count(),
            'active' => Device::where('status', DeviceStatus::ACTIVE)->count(),
            'medical' => Device::whereHas('deviceType', fn ($dt) => $dt->where('category', DeviceCategory::MEDICAL))->count(),
            'life_safety' => Device::where('criticality', DeviceCriticality::LIFE_SAFETY)->count(),
            'interfaces' => DeviceInterface::count(),
        ];

        $viewDevice = $this->viewDeviceId ? Device::with(['deviceType', 'manufacturer', 'deviceModel', 'site', 'department', 'location', 'owner', 'interfaces.vlan'])->find($this->viewDeviceId) : null;
        $deviceAudits = $viewDevice ? AuditEvent::where('subject_type', Device::class)->where('subject_id', $viewDevice->id)->latest('id')->take(10)->get() : collect();

        return view('livewire.devices.index', [
            'devices' => $devices,
            'kpi' => $kpi,
            'viewDevice' => $viewDevice,
            'deviceAudits' => $deviceAudits,
            'sites' => Site::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'deviceTypes' => DeviceType::orderBy('name')->get(),
            'manufacturers' => Manufacturer::orderBy('name')->get(),
            'deviceModels' => DeviceModel::when($this->device_manufacturer_id, fn ($q) => $q->where('manufacturer_id', $this->device_manufacturer_id))->orderBy('name')->get(),
            'locations' => Location::when($this->device_site_id, fn ($q) => $q->where('site_id', $this->device_site_id))->orderBy('name')->get(),
            'vlans' => Vlan::orderBy('vlan_id')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
