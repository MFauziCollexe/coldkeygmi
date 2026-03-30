const STORAGE_KEY = 'gmiic.checklist.entries';

function normalizeEntry(entry) {
  return JSON.parse(JSON.stringify(entry));
}

function isBrowser() {
  return typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
}

export function loadChecklistEntries() {
  if (!isBrowser()) {
    return [];
  }

  try {
    const rawValue = window.localStorage.getItem(STORAGE_KEY);
    return rawValue ? JSON.parse(rawValue) : [];
  } catch (error) {
    return [];
  }
}

export function saveChecklistEntries(entries) {
  if (!isBrowser()) {
    return;
  }

  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(entries));
}

export function appendChecklistEntry(entry) {
  const entries = loadChecklistEntries();
  const updatedEntries = [normalizeEntry(entry), ...entries];
  saveChecklistEntries(updatedEntries);
  return updatedEntries;
}

export function findChecklistEntry(entryId) {
  return loadChecklistEntries().find((entry) => entry.id === entryId) || null;
}

export function upsertChecklistEntry(entry) {
  const normalizedEntry = normalizeEntry(entry);
  const entries = loadChecklistEntries();
  const existingIndex = entries.findIndex((item) => item.id === normalizedEntry.id);

  if (existingIndex === -1) {
    const updatedEntries = [normalizedEntry, ...entries];
    saveChecklistEntries(updatedEntries);
    return updatedEntries;
  }

  const updatedEntries = [...entries];
  updatedEntries[existingIndex] = normalizedEntry;
  saveChecklistEntries(updatedEntries);
  return updatedEntries;
}

export function removeChecklistEntry(entryId) {
  const updatedEntries = loadChecklistEntries().filter((entry) => entry.id !== entryId);
  saveChecklistEntries(updatedEntries);
  return updatedEntries;
}

export function removeChecklistEntries(entryIds = []) {
  const idsToRemove = new Set(entryIds.map((entryId) => String(entryId)));
  const updatedEntries = loadChecklistEntries().filter((entry) => !idsToRemove.has(String(entry.id)));
  saveChecklistEntries(updatedEntries);
  return updatedEntries;
}
