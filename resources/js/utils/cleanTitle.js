/**
 * Display title for an article card: drops the brand name that old posts carry for SEO
 * ("… - Tasfia Door Repair", "… by Tasfia Team", "… - Tasfia Engineering: Expert Finish").
 * The stored title is left as it is.
 */
export function cleanTitle(title = '') {
  const original = String(title).trim();
  const clean = original
    // Brand in the middle: "Plastering Services by Tasfia Engineering: Expert Finish" -> "Plastering Services: Expert Finish"
    .replace(/(?:\s*[-–|]|\s+by)\s*Tasfia(\s+[\w&]+){0,3}\s*:\s*/i, ': ')
    // Brand at the end: "… - Tasfia Door Repair", "… by Tasfia Team", "… and Tasfia Engineering"
    .replace(/\s*(?:[-–|:]|\s(?:by|and|from))\s*Tasfia(\s+[\w&]+){0,3}\s*$/i, '')
    // Brand after a plain space: "… Comprehensive Guide Tasfia Engineering"
    .replace(/\s+Tasfia\s+Engineering\s*$/i, '')
    .trim();

  // Never leave a stub: a title that was mostly the brand stays as it was.
  return clean.length >= 12 ? clean : original;
}
