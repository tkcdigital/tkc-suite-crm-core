import {Component} from '@angular/core';
import {Option, RadioEnumEditFieldComponent} from 'core';

/**
 * TKC SUITE-186: radio group with an accessible name and a visible label for the empty option.
 * Subclass of the core component with no own constructor, so core constructor changes stay safe.
 */
@Component({
    selector: 'tkc-radioenum-edit',
    templateUrl: './tkc-radioenum-edit.component.html',
    styleUrls: []
})
export class TkcRadioEnumEditFieldComponent extends RadioEnumEditFieldComponent {

    /** Accessible name of the radio group: the field label, else the label looked up from vname. */
    get groupLabel(): string {
        const field = this.field;
        if (!field) {
            return '';
        }
        if (field.label) {
            return field.label;
        }
        const vname = field.definition?.vname;
        return this.languages.getFieldLabel(vname, this.record?.module) || '';
    }

    /** Label of an option. The empty key without a list label shows LBL_TKC_NOT_SET. */
    optionLabel(item: Option): string {
        if (item.value === '' && !item.label) {
            return this.languages.getAppString('LBL_TKC_NOT_SET') || '';
        }
        return item.label;
    }
}
