// KYAMCH Smart IPAM - Enterprise Frontend Application Script

function registerSearchableSelect() {
    if (window.Alpine && !window.__searchableSelectRegistered) {
        window.__searchableSelectRegistered = true;

        window.Alpine.data('searchableSelect', (config = {}) => ({
            open: false,
            search: '',
            highlightIndex: -1,
            options: [],
            selectedValue: '',
            selectedLabel: '',
            placeholder: config.placeholder || 'Select an option...',

            init() {
                this.syncFromSelect();

                const select = this.$el.querySelector('select');
                if (select) {
                    select.addEventListener('change', () => {
                        this.syncFromSelect();
                    });

                    // Observe options mutations from Livewire DOM morphs
                    const observer = new MutationObserver(() => {
                        this.syncFromSelect();
                    });
                    observer.observe(select, { childList: true, subtree: true, attributes: true });

                    // Watch Livewire model changes if component has $wire
                    try {
                        let wireModel = null;
                        for (let attr of select.attributes) {
                            if (attr.name === 'wire:model' || attr.name.startsWith('wire:model.')) {
                                wireModel = attr.value;
                                break;
                            }
                        }
                        if (wireModel && this.$wire) {
                            this.$watch(`$wire.${wireModel}`, (newVal) => {
                                if (select && select.value != newVal) {
                                    select.value = newVal;
                                }
                                this.syncFromSelect();
                            });
                        }
                    } catch (e) {}
                }

                this.$watch('search', () => {
                    this.highlightIndex = 0;
                });
            },

            getLivewire() {
                try {
                    if (this.$wire) return this.$wire;
                } catch (e) {}
                try {
                    const select = this.$el.querySelector('select');
                    const root = (select || this.$el).closest('[wire\\:id]');
                    if (root && window.Livewire) {
                        return window.Livewire.find(root.getAttribute('wire:id'));
                    }
                } catch (e) {}
                return null;
            },

            syncFromSelect() {
                const select = this.$el.querySelector('select');
                if (!select) return;

                const opts = [];
                for (let i = 0; i < select.options.length; i++) {
                    const opt = select.options[i];
                    opts.push({
                        value: opt.value,
                        label: opt.text.trim(),
                        disabled: opt.disabled,
                    });
                }
                this.options = opts;
                this.selectedValue = select.value;

                const currentOpt = opts.find(o => String(o.value) === String(select.value));
                if (currentOpt && currentOpt.label) {
                    this.selectedLabel = currentOpt.label;
                } else if (opts.length > 0 && select.selectedIndex >= 0 && opts[select.selectedIndex]) {
                    this.selectedLabel = opts[select.selectedIndex].label;
                } else {
                    this.selectedLabel = this.placeholder;
                }
            },

            toggle() {
                if (this.open) {
                    this.close();
                } else {
                    this.openDropdown();
                }
            },

            openDropdown() {
                this.syncFromSelect();
                this.open = true;
                this.search = '';
                this.highlightIndex = -1;
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            },

            close() {
                this.open = false;
                this.search = '';
                this.highlightIndex = -1;
            },

            get filteredOptions() {
                if (!this.search || !this.search.trim()) {
                    return this.options;
                }
                const q = this.search.toLowerCase().trim();
                return this.options.filter(o =>
                    o.label.toLowerCase().includes(q) || String(o.value).toLowerCase().includes(q)
                );
            },

            selectOption(val) {
                const select = this.$el.querySelector('select');
                if (select) {
                    select.value = val;
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));

                    // Direct Livewire integration
                    try {
                        const lw = this.getLivewire();
                        if (lw) {
                            let wireModel = null;
                            for (let attr of select.attributes) {
                                if (attr.name === 'wire:model' || attr.name.startsWith('wire:model.')) {
                                    wireModel = attr.value;
                                    break;
                                }
                            }
                            if (wireModel) {
                                const numVal = Number(val);
                                const finalVal = (!isNaN(numVal) && String(numVal) === String(val).trim()) ? numVal : val;
                                lw.set(wireModel, finalVal);
                            }

                            const wireChange = select.getAttribute('wire:change');
                            if (wireChange) {
                                if (wireChange.includes('(')) {
                                    lw.$call(wireChange);
                                } else {
                                    lw.call(wireChange, val);
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('[searchableSelect] Livewire sync error:', e);
                    }
                }
                this.selectedValue = val;
                const opt = this.options.find(o => String(o.value) === String(val));
                if (opt) {
                    this.selectedLabel = opt.label;
                }
                this.close();
            },

            onKeyDown(e) {
                if (!this.open) {
                    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        this.openDropdown();
                    }
                    return;
                }

                const filtered = this.filteredOptions;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.close();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (this.highlightIndex < filtered.length - 1) {
                        this.highlightIndex++;
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (this.highlightIndex > 0) {
                        this.highlightIndex--;
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.highlightIndex >= 0 && this.highlightIndex < filtered.length) {
                        this.selectOption(filtered[this.highlightIndex].value);
                    } else if (filtered.length === 1) {
                        this.selectOption(filtered[0].value);
                    }
                }
            }
        }));
    }
}

document.addEventListener('alpine:init', registerSearchableSelect);
document.addEventListener('livewire:init', registerSearchableSelect);
document.addEventListener('livewire:navigated', registerSearchableSelect);
if (window.Alpine) {
    registerSearchableSelect();
}
