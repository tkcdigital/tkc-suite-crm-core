import {ComponentFixture, TestBed, waitForAsync} from '@angular/core/testing';
import {Component, ViewChild} from '@angular/core';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {UntypedFormControl} from '@angular/forms';
import {of} from 'rxjs';
import {DataTypeFormatter, Field, FieldLogicDisplayManager, FieldHandlerRegistry, FieldLogicManager, LanguageStore} from 'core';
import {TkcRadioEnumEditFieldComponent} from './tkc-radioenum-edit.component';
import {TkcRadioEnumEditFieldModule} from './tkc-radioenum-edit.module';

const appStrings: { [key: string]: string } = {LBL_TKC_NOT_SET: 'Not set'};
const listStrings: { [key: string]: { [key: string]: string } } = {
    tkc_yes_no_list: {'': '', '1': 'Yes', '0': 'No'},
    tkc_labelled_list: {'': 'Unknown', '1': 'Yes'}
};

const languageStoreStub: any = {
    vm$: of({appStrings, appListStrings: listStrings, modStrings: {}}),
    getFieldLabel: (key: string) => (key === 'LBL_TEST' ? 'Test label' : ''),
    getAppString: (key: string) => appStrings[key] ?? '',
    getAppListString: (key: string) => listStrings[key],
    getLanguageStrings: () => ({appStrings, appListStrings: listStrings, modStrings: {}}),
};

@Component({
    selector: 'tkc-radioenum-test-host',
    template: '<tkc-radioenum-edit #fieldComponent [field]="field"></tkc-radioenum-edit>'
})
class HostComponent {
    @ViewChild('fieldComponent') fieldComponent: TkcRadioEnumEditFieldComponent;
    field: Field = {
        name: 'tkc_test_c',
        type: 'enum',
        value: '1',
        metadata: null,
        definition: {options: 'tkc_yes_no_list', vname: 'LBL_TEST'},
        formControl: new UntypedFormControl('1')
    } as Field;
}

describe('TkcRadioEnumEditFieldComponent', () => {
    let fixture: ComponentFixture<HostComponent>;
    let host: HostComponent;

    beforeEach(waitForAsync(() => {
        TestBed.configureTestingModule({
            declarations: [HostComponent],
            imports: [BrowserDynamicTestingModule, BrowserAnimationsModule, TkcRadioEnumEditFieldModule],
            providers: [
                {provide: LanguageStore, useValue: languageStoreStub},
                {provide: FieldHandlerRegistry, useValue: {get: () => ({})}},
                {provide: DataTypeFormatter, useValue: {}},
                {provide: FieldLogicManager, useValue: {runLogic: () => undefined}},
                {provide: FieldLogicDisplayManager, useValue: {runAll: () => undefined}},
            ],
        }).compileComponents();
    }));

    function create(): HTMLElement {
        fixture = TestBed.createComponent(HostComponent);
        host = fixture.componentInstance;
        fixture.detectChanges();
        return fixture.nativeElement.querySelector('.radioenum');
    }

    it('has role radiogroup with an accessible name', () => {
        const group = create();
        expect(group.getAttribute('role')).toBe('radiogroup');
        expect(group.getAttribute('aria-label')).toBe('Test label');
    });

    it('shows "Not set" for the empty key with an empty label', () => {
        const group = create();
        const labels = Array.from(group.querySelectorAll('label')).map(l => l.textContent.trim());
        expect(labels).toContain('Not set');
        expect(labels).toContain('Yes');
        expect(labels).toContain('No');
    });

    it('keeps the list label for the empty key when it is not empty', () => {
        fixture = TestBed.createComponent(HostComponent);
        fixture.componentInstance.field.definition.options = 'tkc_labelled_list';
        fixture.detectChanges();
        const labels = Array.from(fixture.nativeElement.querySelectorAll('label')).map((l: HTMLElement) => l.textContent.trim());
        expect(labels).toContain('Unknown');
        expect(labels).not.toContain('Not set');
    });

    it('gives all radios one shared name', () => {
        const group = create();
        const names = Array.from(group.querySelectorAll('input[type=radio]')).map((i: HTMLInputElement) => i.name);
        expect(names.length).toBe(3);
        expect(new Set(names)).toEqual(new Set(['tkc_test_c']));
    });
});
