import { ref, computed, watch } from 'vue';

/**
 * Normalize a PIC string by stripping any leading "PIC" prefix for comparison.
 *
 * @param {string|null|undefined} value
 * @returns {string}
 */
export function normalizePicName(value) {
  return String(value || '')
    .replace(/^PIC\s*[:\-]?\s*/i, '')
    .trim()
    .toUpperCase();
}

/**
 * Manage Excel export query parameters (PIC, school, jenjang, deduplication)
 * synchronized with active page filters.
 */
export function useShoeExport({
  getUrl,
  selectedSchool,
  selectedPic,
  selectedJenjang = null,
  schools,
  initialPic = '',
  baseLabel = 'Export Excel',
}) {
  const deduplicateExport = ref(false);
  const exportPic = ref(initialPic || '');

  watch(selectedPic, (newPic) => {
    exportPic.value = newPic || '';
  });

  const activeSchoolObj = computed(() => {
    if (!selectedSchool.value) return null;
    const list = typeof schools === 'function' ? schools() : (schools?.value ?? schools ?? []);
    return list.find((s) => s.id === Number(selectedSchool.value)) || null;
  });

  const excelExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (exportPic.value) {
      params.append('pic', exportPic.value);
    }

    if (selectedSchool.value) {
      // Omit school_id only if user picked a different PIC directly in the export dropdown
      if (!exportPic.value) {
        params.append('school_id', selectedSchool.value);
      } else if (exportPic.value === '__none__') {
        if (!activeSchoolObj.value || !normalizePicName(activeSchoolObj.value.pic_nama)) {
          params.append('school_id', selectedSchool.value);
        }
      } else if (
        activeSchoolObj.value &&
        normalizePicName(activeSchoolObj.value.pic_nama) === normalizePicName(exportPic.value)
      ) {
        params.append('school_id', selectedSchool.value);
      }
    }

    if (selectedJenjang?.value) {
      params.append('jenjang', selectedJenjang.value);
    }
    if (deduplicateExport.value) {
      params.append('deduplicate', '1');
    }

    const query = params.toString();
    return getUrl(query ? `/shoes/export/excel?${query}` : '/shoes/export/excel');
  });

  const exportButtonLabel = computed(() => {
    const parts = [];
    if (exportPic.value) {
      parts.push(exportPic.value === '__none__' ? 'Tanpa PIC' : `PIC: ${exportPic.value}`);
    }
    if (deduplicateExport.value) {
      parts.push('Tanpa Duplikat');
    }
    return parts.length > 0 ? `${baseLabel} (${parts.join(' • ')})` : baseLabel;
  });

  return {
    deduplicateExport,
    exportPic,
    activeSchoolObj,
    excelExportUrl,
    exportButtonLabel,
  };
}

