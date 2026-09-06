<?php
// Deliberately minimal: most of this codebase's functions are tightly
// coupled to a live DB connection (TB_PREF, db_query, $_SESSION), which is
// out of scope for a unit-test bootstrap. Only pure, DB-free functions
// (see tests/knb_schemes/) are unit tested here; anything needing a real
// database is verified manually against the local dev instance instead
// (see docs/mvp-scope.md for what's been tested that way).
