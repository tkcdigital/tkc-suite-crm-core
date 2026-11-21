import {TestBed} from '@angular/core/testing';
import {ExtensionModule} from './extension.module';

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
});
