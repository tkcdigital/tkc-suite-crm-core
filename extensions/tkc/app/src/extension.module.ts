import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {provideHttpClient, withInterceptorsFromDi} from '@angular/common/http';
import {FieldRegistry} from 'core';
import {TkcRadioEnumEditFieldComponent} from './fields/radioenum/tkc-radioenum-edit.component';
import {TkcRadioEnumEditFieldModule} from './fields/radioenum/tkc-radioenum-edit.module';

@NgModule({ declarations: [], imports: [CommonModule, TkcRadioEnumEditFieldModule], providers: [provideHttpClient(withInterceptorsFromDi())] })
export class ExtensionModule {
    constructor(protected fieldRegistry: FieldRegistry) {
        // SUITE-186: replace the core radio edit component for both display types (same core class).
        this.fieldRegistry.register('default', 'radioenum', 'edit', TkcRadioEnumEditFieldComponent);
        this.fieldRegistry.register('default', 'enum-radio', 'edit', TkcRadioEnumEditFieldComponent);
    }
    init(): void {
    }
}
