import {TestBed} from '@angular/core/testing';
import {FieldRegistry} from 'core';
import {ExtensionModule} from './extension.module';
import {TkcRadioEnumEditFieldComponent} from './fields/radioenum/tkc-radioenum-edit.component';

describe('ExtensionModule', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({imports: [ExtensionModule]}).compileComponents();
    });

    it('compiles and can be injected', () => {
        expect(TestBed.inject(ExtensionModule)).toBeTruthy();
    });

    it('has an init() method', () => {
        const module = TestBed.inject(ExtensionModule);
        expect(typeof module.init).toBe('function');
        expect(() => module.init()).not.toThrow();
    });

    it('registers the TKC radio edit component for radioenum and enum-radio (SUITE-186)', () => {
        TestBed.inject(ExtensionModule);
        const registry = TestBed.inject(FieldRegistry);
        expect(registry.get('default', 'radioenum', 'edit', 'x')).toBe(TkcRadioEnumEditFieldComponent);
        expect(registry.get('default', 'enum-radio', 'edit', 'x')).toBe(TkcRadioEnumEditFieldComponent);
        expect(registry.getDisplayType('accounts', 'enum', 'radio', 'edit', 'x')).toBe(TkcRadioEnumEditFieldComponent);
    });
});
