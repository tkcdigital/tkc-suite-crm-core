"""Unit tests for scripts/theme/build_theme.py. Synthetic mini tokens only."""
import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import build_theme as bt  # noqa: E402


def mini_tokens(brand_hue="195"):
    return {
        "$schema": "tkc-tokens/1",
        "primitive": {
            "color": {"brand": {"700": {"value": "oklch(0.5 0.1 var(--tkc-brand-hue))", "css": "--tkc-color-brand-700"}}},
            "space": {"4": {"value": "1rem", "css": "--tkc-space-4"}, "half": {"value": "0.5rem", "css": "--tkc-space-half"}},
            "duration": {"fast": {"value": "120ms", "css": "--tkc-duration-fast"}},
            "brand": {"hue": {"value": brand_hue, "css": "--tkc-brand-hue"}},
        },
        "semantic": {
            "light": {"text": {"link": {"value": "{primitive.color.brand.700}", "css": "--tkc-text-link"}}},
            "dark": {"text": {"link": {"value": "{primitive.color.brand.700}", "css": "--tkc-text-link"}}},
        },
        "density": {
            "compact": {"row-height": {"value": "2rem", "css": "--tkc-density-row-height"}},
            "comfortable": {"row-height": {"value": "3rem", "css": "--tkc-density-row-height"}},
        },
    }


class Base(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.write_tokens(mini_tokens())
        (self.root / bt.LEGACY_DIR_REL).mkdir(parents=True)
        (self.root / bt.ANGULAR_THEME_DIR_REL).mkdir(parents=True)

    def tearDown(self):
        self._tmp.cleanup()

    def write_tokens(self, tokens):
        path = self.root / bt.TOKENS_REL
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(tokens, indent=2), encoding="utf-8")


class TestRender(Base):
    def test_reference_resolves_to_var(self):
        out = bt.render_all(self.root)[bt.OUT_SCSS_REL]
        self.assertIn("--tkc-text-link: var(--tkc-color-brand-700);", out)

    def test_unresolved_reference_fails(self):
        t = mini_tokens()
        t["semantic"]["light"]["text"]["link"]["value"] = "{primitive.color.nope.1}"
        self.write_tokens(t)
        with self.assertRaises(bt.TokenError):
            bt.render_all(self.root)

    def test_missing_css_name_fails(self):
        t = mini_tokens()
        del t["primitive"]["space"]["4"]["css"]
        self.write_tokens(t)
        with self.assertRaises(bt.TokenError):
            bt.render_all(self.root)

    def test_main_returns_1_on_bad_tokens(self):
        t = mini_tokens()
        t["semantic"]["light"]["text"]["link"]["value"] = "{primitive.x}"
        self.write_tokens(t)
        self.assertEqual(bt.main(["--root", str(self.root)]), 1)

    def test_dark_only_under_data_theme(self):
        out = bt.render_all(self.root)[bt.OUT_SCSS_REL]
        self.assertIn(':root[data-theme="dark"] {', out)
        self.assertNotIn("prefers-color-scheme", out)
        auto = bt.render_all(self.root, dark_auto=True)[bt.OUT_SCSS_REL]
        self.assertIn("prefers-color-scheme: dark", auto)

    def test_light_and_dark_blocks_exist(self):
        out = bt.render_all(self.root)[bt.OUT_SCSS_REL]
        self.assertIn("color-scheme: light;", out)
        self.assertIn("color-scheme: dark;", out)

    def test_density_blocks(self):
        out = bt.render_all(self.root)[bt.OUT_SCSS_REL]
        self.assertIn(':root[data-density="comfortable"]', out)
        self.assertIn("@media (pointer: coarse)", out)

    def test_rem_to_px_legacy_only(self):
        outs = bt.render_all(self.root)
        self.assertIn("--tkc-space-4: 1rem;", outs[bt.OUT_SCSS_REL])
        for rel in (bt.OUT_LEGACY_TOKENS_REL, bt.OUT_STYLE_REL):
            self.assertIn("--tkc-space-4: 16px;", outs[rel])
            self.assertIn("--tkc-space-half: 8px;", outs[rel])
            self.assertNotRegex(outs[rel], r"\d(\.\d+)?rem")

    def test_rem_to_px_fractions(self):
        self.assertEqual(bt.rem_to_px("0.6875rem 0 -0.25rem"), "11px 0 -4px")

    def test_sentinel_changes_with_tokens(self):
        a = bt.render_all(self.root)[bt.OUT_SCSS_REL]
        self.write_tokens(mini_tokens(brand_hue="210"))
        b = bt.render_all(self.root)[bt.OUT_SCSS_REL]
        self.assertIn("--tkc-theme-build:", a)
        self.assertNotEqual(a, b)

    def test_fonts_use_self_hosted_path(self):
        out = bt.render_all(self.root)[bt.OUT_STYLE_REL]
        self.assertIn('url("/legacy/custom/themes/suite8/fonts/IBMPlexSans-Regular-Latin1.woff2")', out)
        self.assertNotIn("googleapis", out)

    def test_determinism_and_lf(self):
        a = bt.render_all(self.root)
        b = bt.render_all(self.root)
        self.assertEqual(a, b)
        for text in a.values():
            self.assertNotIn("\r", text)
            self.assertTrue(text.endswith("\n"))

    def test_style_joins_partials_in_name_order(self):
        d = self.root / bt.LEGACY_DIR_REL
        (d / "20-b.css").write_text("b{color:var(--tkc-text-link)}", encoding="utf-8")
        (d / "10-a.css").write_text("a{color:var(--tkc-text-link)}\n", encoding="utf-8")
        style = bt.render_all(self.root)[bt.OUT_STYLE_REL]
        self.assertLess(style.index("10-a.css"), style.index("20-b.css"))
        self.assertLess(style.index("--tkc-space-4"), style.index("10-a.css"))


class TestCheck(Base):
    def test_check_fails_when_missing_then_passes_after_write(self):
        self.assertEqual(bt.main(["--root", str(self.root), "--check"]), 1)
        self.assertEqual(bt.main(["--root", str(self.root)]), 0)
        self.assertEqual(bt.main(["--root", str(self.root), "--check"]), 0)

    def test_check_fails_when_tokens_change(self):
        bt.main(["--root", str(self.root)])
        self.write_tokens(mini_tokens(brand_hue="210"))
        self.assertEqual(bt.main(["--root", str(self.root), "--check"]), 1)

    def test_check_fails_when_generated_file_edited(self):
        bt.main(["--root", str(self.root)])
        p = self.root / bt.OUT_STYLE_REL
        p.write_text(p.read_text() + "\n/* hand edit */\n", encoding="utf-8")
        self.assertEqual(bt.main(["--root", str(self.root), "--check"]), 1)

    def test_check_fails_on_hex_in_hand_partial(self):
        bt.main(["--root", str(self.root)])
        (self.root / bt.LEGACY_DIR_REL / "10-base.css").write_text("body { color: #fff; }\n", encoding="utf-8")
        bt.main(["--root", str(self.root)])
        self.assertEqual(bt.main(["--root", str(self.root), "--check"]), 1)


class TestLint(unittest.TestCase):
    def test_hex_found(self):
        self.assertTrue(bt.lint_text("a { color: #fff; }", legacy=True, scss=False))
        self.assertTrue(bt.lint_text("a { color: #1f2525; }", legacy=False, scss=True))
        self.assertTrue(bt.lint_text("a { border: 1px solid #abcd; }", legacy=True, scss=False))

    def test_rgb_hsl_found(self):
        self.assertTrue(bt.lint_text("a { color: rgb(0,0,0); }", True, False))
        self.assertTrue(bt.lint_text("a { color: hsla(0,0%,0%,.5); }", True, False))

    def test_clean_css(self):
        self.assertEqual(bt.lint_text("a { color: var(--tkc-text-default); }", True, False), [])

    def test_id_selector_not_flagged(self):
        self.assertEqual(bt.lint_text("#add, #fade { margin: 0; }", True, False), [])

    def test_comments_ignored(self):
        self.assertEqual(bt.lint_text("/* #fff */ a { margin: 0; }", True, False), [])
        self.assertEqual(bt.lint_text("// #fff\na { margin: 0; }", False, True), [])

    def test_space_before_pseudo_legacy_only(self):
        for sel in ("body :is(a, b):focus-visible", ".a :hover", ".a\n  :not(.b)", "a, b :first-child"):
            css = sel + " { color: var(--x); }"
            self.assertEqual(len(bt.lint_text(css, True, False)), 1, sel)
            self.assertEqual(bt.lint_text(css, False, True), [], sel)

    def test_z_index_overlay_token(self):
        for tok in ("modal", "toast", "dropdown", "drawer", "tooltip"):
            css = ".m { z-index: var(--tkc-z-%s); }" % tok
            self.assertEqual(len(bt.lint_text(css, False, True)), 1, tok)
            self.assertEqual(len(bt.lint_text(css, True, False)), 1, tok)
        ok = ".n { z-index: var(--tkc-z-navbar); } .s { z-index: var(--tkc-z-sticky); } .b { z-index: 1; }"
        self.assertEqual(bt.lint_text(ok, False, True), [])

    def test_space_before_pseudo_no_false_positive(self):
        css = (
            "a:hover, a:focus-visible:focus-visible, *:is(a, b) { color: var(--x); }\n"
            "a, :hover { color: var(--x); }\n"
            "@media (min-width: 600px) { .a { color: var(--x); } }\n"
            ".b { margin: 0 auto; transition: color 1s; }\n"
        )
        self.assertEqual(bt.lint_text(css, True, False), [])

    def test_calc_plus_legacy_only(self):
        css = "a { width: calc(100% + 4px); }"
        self.assertTrue(bt.lint_text(css, legacy=True, scss=False))
        self.assertEqual(bt.lint_text(css, legacy=False, scss=True), [])
        self.assertEqual(bt.lint_text("a { width: calc(100% - 4px); }", True, False), [])


if __name__ == "__main__":
    unittest.main()
