import { ref, computed } from 'vue';

// Reactive singleton state for sidebar drawer & collapse
const isSidebarOpen = ref(false); // Mobile drawer state

const isCollapsed = ref(
  typeof window !== 'undefined'
    ? localStorage.getItem('hybrid_sidebar_collapsed') === 'true'
    : false
);

// Synchronize storage across tabs/windows
if (typeof window !== 'undefined') {
  window.addEventListener('storage', (e) => {
    if (e.key === 'hybrid_sidebar_collapsed') {
      isCollapsed.value = e.newValue === 'true';
    }
  });
}

export function useSidebar() {
  const toggleSidebar = () => {
    if (typeof window !== 'undefined' && window.innerWidth >= 1024) {
      toggleCollapse();
    } else {
      isSidebarOpen.value = !isSidebarOpen.value;
    }
  };

  const closeMobileSidebar = () => {
    isSidebarOpen.value = false;
  };

  const openMobileSidebar = () => {
    isSidebarOpen.value = true;
  };

  const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    if (typeof window !== 'undefined') {
      localStorage.setItem('hybrid_sidebar_collapsed', isCollapsed.value.toString());
    }
  };

  const setCollapsed = (val) => {
    isCollapsed.value = val;
    if (typeof window !== 'undefined') {
      localStorage.setItem('hybrid_sidebar_collapsed', val.toString());
    }
  };

  // Layout offset classes for the page outer wrapper
  // When expanded: lg:pl-64 (256px), when collapsed: lg:pl-20 (80px)
  const layoutPaddingClass = computed(() => {
    return isCollapsed.value ? 'lg:pl-20' : 'lg:pl-64';
  });

  const sidebarWidthClass = computed(() => {
    return isCollapsed.value ? 'w-20' : 'w-64';
  });

  return {
    isSidebarOpen,
    isCollapsed,
    toggleSidebar,
    closeMobileSidebar,
    openMobileSidebar,
    toggleCollapse,
    setCollapsed,
    layoutPaddingClass,
    sidebarWidthClass,
  };
}

export default useSidebar;

