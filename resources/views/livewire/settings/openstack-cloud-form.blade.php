<?php

use App\Services\OpenstackYamlService;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Symfony\Component\Yaml\Yaml;

new class extends Component {
    use WithFileUploads;

    #[Validate([
        'required',
        'file',
        'mimes:yaml,yml',
        'max:2048', // 2MB max
    ])]
    public ?TemporaryUploadedFile $cloud_yaml = null;

    public string $cloud_yaml_content = '';
    public bool $is_password_less = false;

    public array $cloudYamlData = [];
    public bool $isContentDisplayable = false;

    private readonly OpenstackYamlService $openstackYamlService;

    public function __construct()
    {
        parent::__construct();

        $this->openstackYamlService = app(OpenstackYamlService::class);
    }

    public function rendering(): void
    {
        if ($this->cloud_yaml instanceof TemporaryUploadedFile) {
            $this->cloud_yaml_content = $this->cloud_yaml->get();

            $this->cloud_yaml->delete();
            $this->cloud_yaml = null;
        }

        if ($this->cloud_yaml_content !== '' && $this->cloud_yaml_content !== '0') {
            $this->sanitizeYamlContent();
            $this->isContentDisplayable = false;
            $this->resetErrorBag(['cloud_yaml_content']);
        }
    }

    public function save(): void
    {
        if ($this->cloud_yaml_content === '' || $this->cloud_yaml_content === '0' || $this->cloud_yaml_content === $this->getDefaultCloudYaml()) {
            $this->isContentDisplayable = true;
            $this->addError('cloud_yaml_content', __('Le contenu du fichier YAML est requis.'));
            return;
        }

        try {
            $this->openstackYamlService->createCloudEntry($this->cloud_yaml_content);
        } catch (Throwable $e) {
            $this->isContentDisplayable = true;
            $this->addError('cloud_yaml_content', __('Erreur lors de la sauvegarde du fichier YAML : :message', ['message' => $e->getMessage()]));
            return;
        }

        $this->dispatch('configCreated');
        $this->modal('new-config')->close();
    }

    public function reboot(): void
    {
        $this->cloud_yaml = null;
        $this->cloud_yaml_content = '';
        $this->isContentDisplayable = false;
        $this->resetErrorBag();
    }

    public function setDefault(): void
    {
        $this->cloud_yaml_content = $this->getDefaultCloudYaml();
    }

    private function sanitizeYamlContent(): void
    {
        if ($this->cloud_yaml_content === '' || $this->cloud_yaml_content === '0') {
            return;
        }

        if ($this->is_password_less) {
            $pattern = '/^([ ]*?)password:(.*)$/m';
            $replacement = '$1password-less: true,';
        } else {
            $pattern = '/^([ ]*?)password-less:(.*)$/m';
            $replacement = '$1password:$2';
        }

        $this->cloud_yaml_content = preg_replace($pattern, $replacement, $this->cloud_yaml_content);
    }

    private function getDefaultCloudYaml(): string
    {
        return Storage::disk('local')->get('default-cloud.yaml');
    }
}; ?>

<section class="w-fit">
    <flux:modal.trigger name="new-config">
        <flux:button icon="plus" />
    </flux:modal.trigger>

    <flux:modal name="new-config" class="min-w-1/3 pr-16" variant="flyout">
        <!-- BUTTON to get YAML content -->
        <div class="grid grid-cols-2 gap-x-4 gap-y-6">
            <flux:input
                    wire:model.blur="cloud_yaml"
                    error="cloud_yaml"
                    type="file"
                    label="{{ __('YAML de configuration') }}"
            />

            <flux:button
                    variant="subtle"
                    size="xs"
                    wire:click="setDefault"
                    icon="pencil"
            >
                @lang('Utiliser un template et remplir à la main')
            </flux:button>
        </div>

        <!-- CONDITIONAL DISPLAY of the YAML content -->
        @if(! empty($this->cloud_yaml_content) || $this->isContentDisplayable)
            <div class="mt-2">
                <flux:textarea
                        wire:model.blur="cloud_yaml_content"
                        label="{{ __('Contenu du fichier YAML') }}"
                        rows="auto"
                        class="w-max"
                >
                    {{ $this->cloud_yaml_content }}
                </flux:textarea>
            </div>

            <flux:error wire:model="cloud_yaml_content" class="mt-2" />
        @endif

        <!-- CHECKBOX that toggles the password-less mode -->
        <div class="mt-4">
            <flux:field variant="inline">
                <flux:checkbox wire:model.live="is_password_less" />

                <flux:label>@lang('Utiliser un accès sans mot de passe')</flux:label>

                <flux:error name="is_password_less" />
            </flux:field>

            <flux:text variant="subtle" class="mt-2">
                @lang('Si vous cochez cette case, vous devrez le taper manuellement lors de chaque appel à openstack.')
            </flux:text>
        </div>

        <!-- BUTTON to save or reboot -->
        <div class="grid grid-cols-3 gap-x-4 gap-y-6 items-center mt-8">
            <flux:button variant="primary" wire:click="save">
                @lang('Enregistrer')
            </flux:button>

            <flux:button variant="subtle" wire:click="reboot" size="sm">
                @lang('Réinitialiser')
            </flux:button>
        </div>
    </flux:modal>
</section>
