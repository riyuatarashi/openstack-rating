<?php

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

    public array $coudYamlData = [];
    public string $content = '';
    public bool $isPasswordLess = false;

    public bool $isContentDisplayable = false;

    public function rendering(): void
    {
        if (! empty($this->cloud_yaml)) {
            $this->content = $this->cloud_yaml->get();

            $this->cloud_yaml->delete();
            $this->cloud_yaml = null;
        }

        if (! empty($this->content)) {
            $this->sanitizeYamlContent();
            $this->isContentDisplayable = false;
            $this->resetErrorBag(['content']);
        }
    }

    public function save(): void
    {
        if (empty($this->content) || $this->content === $this->getDefaultCloudYaml()) {
            $this->isContentDisplayable = true;
            $this->addError('content', __('Le contenu du fichier YAML est requis.'));
            return;
        }

        // Here you would typically save the content to a file or database
        // For demonstration, we will just log it
        ray($this->content)->green();
    }

    public function reboot(): void
    {
        $this->cloud_yaml = null;
        $this->content = '';
        $this->isContentDisplayable = false;
        $this->resetErrorBag();
    }

    public function setDefault(): void
    {
        $this->content = $this->getDefaultCloudYaml();
    }

    private function sanitizeYamlContent(): void
    {
        if (empty($this->content)) {
            return;
        }

        if ($this->isPasswordLess) {
            $pattern = '/^([ ]*?)password:(.*)$/m';
            $replacement = '$1password-less: true,';
        } else {
            $pattern = '/^([ ]*?)password-less:(.*)$/m';
            $replacement = '$1password:$2';
        }

        $this->content = preg_replace($pattern, $replacement, $this->content);
    }

    private function getDefaultCloudYaml(): string
    {
        return Storage::disk('local')
            ->get('default-cloud.yaml');
    }
}; ?>

<section>
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
    @if(! empty($this->content) || $this->isContentDisplayable)
        <div class="mt-2">
            <flux:textarea
                    wire:model.blur="content"
                    label="{{ __('Contenu du fichier YAML') }}"
                    rows="auto"
                    class="w-max"
            >
                {{ $this->content }}
            </flux:textarea>
        </div>

        <flux:error wire:model="content" class="mt-2" />
    @endif

    <!-- CHECKBOX that toggles the password-less mode -->
    <div class="mt-4">
        <flux:field variant="inline">
            <flux:checkbox wire:model.live="isPasswordLess" />

            <flux:label>@lang('Utiliser un accès sans mot de passe')</flux:label>

            <flux:error name="isPasswordLess" />
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
</section>
