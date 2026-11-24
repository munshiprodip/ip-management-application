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

                    // Watch Livewire model changes if component has $wire or getLivewire()
                    try {
                        let wireModel = null;
                        for (let attr of select.attributes) {
                            if (attr.name === 'wire:model' || attr.name.startsWith('wire:model.')) {
                                wireModel = attr.value;
                                break;
                            }
                        }
                        const lw = this.getLivewire();
                        if (wireModel && lw && typeof lw.$watch === 'function') {
                            lw.$watch(wireModel, (newVal) => {
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
                if (config && config.wire) return config.wire;
                try {
                    if (this.$wire) return this.$wire;
                } catch (e) {}
                try {
                    let cur = this.$el;
                    while (cur) {
                        if (cur.__livewire) {
                            return cur.__livewire.$wire || cur.__livewire;
                        }
                        if (cur.hasAttribute && cur.hasAttribute('wire:snapshot')) {
                            if (window.Livewire && typeof window.Livewire.all === 'function') {
                                for (let comp of window.Livewire.all()) {
                                    if (comp.el === cur) {
                                        return comp.$wire || comp;
                                    }
                                }
                            }
                        }
                        cur = cur.parentElement;
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

                let activeVal = select.value;
                if (!activeVal || activeVal === '') {
                    const selOption = select.querySelector('option[selected]') || (select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null);
                    if (selOption && selOption.value) {
                        activeVal = selOption.value;
                        select.value = activeVal;
                    }
                }
                this.selectedValue = activeVal;

                const currentOpt = opts.find(o => String(o.value) === String(activeVal));
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
                    // Ensure matching option is marked selected in DOM
                    for (let i = 0; i < select.options.length; i++) {
                        select.options[i].selected = (String(select.options[i].value) === String(val));
                    }
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
                            const numVal = Number(val);
                            const finalVal = (!isNaN(numVal) && String(numVal) === String(val).trim()) ? numVal : val;

                            if (wireModel) {
                                if (typeof lw.$set === 'function') {
                                    lw.$set(wireModel, finalVal, true);
                                } else if (typeof lw.set === 'function') {
                                    lw.set(wireModel, finalVal, true);
                                } else {
                                    lw[wireModel] = finalVal;
                                }

                                if (wireModel === 'selectedSubnetId') {
                                    try {
                                        const u = new URL(window.location.href);
                                        if (finalVal) {
                                            u.searchParams.set('subnet', finalVal);
                                        } else {
                                            u.searchParams.delete('subnet');
                                        }
                                        window.history.replaceState({}, '', u);
                                    } catch (e) {}
                                }
                            }

                            const wireChange = select.getAttribute('wire:change');
                            if (wireChange) {
                                const methodName = wireChange.replace(/\(.*\)/, '').trim();
                                if (methodName) {
                                    if (typeof lw[methodName] === 'function') {
                                        lw[methodName](finalVal);
                                    } else if (typeof lw.$call === 'function') {
                                        lw.$call(methodName, finalVal);
                                    }
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
