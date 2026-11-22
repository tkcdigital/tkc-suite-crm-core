"""Unit test for check 4 of check_bundle.py (SUITE-194). Inline CSS samples, synthetic."""
import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import check_bundle as cb  # noqa: E402


def parse(css):
    return [([s.strip() for s in m.group(1).split(",")], m.group(2))
            for m in re.finditer(r"(?:^|(?<=\}))\s*([^{}]*)\{([^{}]*)\}", css)]


CORE = (".dropdown-button-secondary .dropdown-menu a{color:#fff}"
        ".button-group-dropdown .dropdown-menu{background-color:#fff}")
MENUS = (".button-group-dropdown .dropdown-menu,.dropdown-button-secondary .dropdown-menu"
         "{background-color:var(--tkc-header-action-menu-bg)}"
         ".button-group-dropdown .dropdown-menu a.dropdown-item,"
         ".dropdown-button-secondary .dropdown-menu a.dropdown-item{color:var(--tkc-header-action-text)}")
BUG = ".btn-secondary,.settings-button,.action-button{color:var(--tkc-header-action-text);background-color:var(--tkc-header-action-bg)}"
FIXED = (".btn-secondary:where(:not(.dropdown-item)),.settings-button:where(:not(.dropdown-item)),"
         ".action-button:where(:not(.dropdown-item)){color:var(--tkc-header-action-text);"
         "background-color:var(--tkc-header-action-bg)}")


class TestMenus(unittest.TestCase):
    def test_bug_fails_on_a(self):
        self.assertTrue(cb.check_menus(parse(CORE + BUG + MENUS)).startswith("(a)"))

    def test_missing_menu_rules_fail(self):
        self.assertTrue(cb.check_menus(parse(CORE + FIXED)).startswith("(b)"))

    def test_missing_core_selector_fails(self):
        self.assertTrue(cb.check_menus(parse(FIXED + MENUS)).startswith("(d)"))

    def test_fixed_passes(self):
        self.assertIsNone(cb.check_menus(parse(CORE + FIXED + MENUS)))


if __name__ == "__main__":
    unittest.main()
