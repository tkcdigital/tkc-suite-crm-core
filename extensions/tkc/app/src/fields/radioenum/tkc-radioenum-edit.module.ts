import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {TkcRadioEnumEditFieldComponent} from './tkc-radioenum-edit.component';

@NgModule({
    declarations: [TkcRadioEnumEditFieldComponent],
    exports: [TkcRadioEnumEditFieldComponent],
    imports: [CommonModule, FormsModule, ReactiveFormsModule]
})
export class TkcRadioEnumEditFieldModule {
}
