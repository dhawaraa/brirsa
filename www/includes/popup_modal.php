<?php
/**
 * Frontend Popup Modal Component
 * ดึงข้อมูล Popups ที่เปิดใช้งานทั้งหมด และแสดงผล Carousel/Slider หน้าบ้าน
 */
if (!function_exists('getDB')) {
    return;
}

try {
    $pdo_popup = getDB();
    $currentDate = date('Y-m-d');
    $currentScript = basename($_SERVER['PHP_SELF']);
    $isHomePage = ($currentScript === 'index.php' || $currentScript === '');

    // Query all active popups within valid date ranges ordered by sort_order
    $sql_popup = "SELECT * FROM popups 
                  WHERE is_active = 1 
                  AND (start_date IS NULL OR start_date <= ?)
                  AND (end_date IS NULL OR end_date >= ?)
                  AND (target_page = 'all' " . ($isHomePage ? "OR target_page = 'home'" : "") . ")
                  ORDER BY sort_order DESC, created_at DESC";

    $stmt_popup = $pdo_popup->prepare($sql_popup);
    $stmt_popup->execute([$currentDate, $currentDate]);
    $activePopups = $stmt_popup->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $activePopups = [];
}

// Filter out items without existing image
$validPopups = [];
foreach ($activePopups as $p) {
    if (!empty($p['image_url']) && file_exists(__DIR__ . '/../' . $p['image_url'])) {
        $validPopups[] = $p;
    }
}

if (!empty($validPopups)): 
    // Create unique combined key for storage check
    $popupIds = array_column($validPopups, 'id');
    $combinedKey = implode('_', $popupIds);
?>

<!-- Announcement Popup Modal Carousel -->
<div id="siteAnnouncementModal" 
     data-popup-key="<?php echo $combinedKey; ?>"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6 lg:p-8 bg-black/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 select-none">
    
    <div class="relative w-full max-w-md md:max-w-xl lg:max-w-[580px] bg-white rounded-3xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300 border border-gray-100/20 flex flex-col max-h-[95vh]">
        
        <!-- Image Slider Container (1:1 Ratio) -->
        <div class="relative w-full aspect-square bg-gray-900 overflow-hidden group">
            
            <!-- Close Button (Top Right Inside Image) -->
            <button type="button" 
                    onclick="closeSitePopup(false)" 
                    class="absolute top-3.5 right-3.5 z-30 w-10 h-10 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80 text-white/90 hover:text-white shadow-lg backdrop-blur-md transition-all duration-200"
                    title="ปิดหน้าต่าง">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Slides -->
            <div id="popupSlidesContainer" class="w-full h-full relative">
                <?php foreach ($validPopups as $index => $popup): ?>
                    <div class="popup-slide absolute inset-0 w-full h-full transition-opacity duration-300 flex items-center justify-center <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'; ?>"
                         data-index="<?php echo $index; ?>"
                         data-id="<?php echo $popup['id']; ?>"
                         data-link="<?php echo htmlspecialchars($popup['link_url'] ?? ''); ?>"
                         data-link-text="<?php echo htmlspecialchars($popup['link_text_th'] ?: 'ดูรายละเอียด'); ?>">
                        
                        <?php if (!empty($popup['link_url'])): ?>
                            <a href="<?php echo htmlspecialchars($popup['link_url']); ?>" target="_blank" rel="noopener noreferrer" class="block w-full h-full">
                                <img src="<?php echo htmlspecialchars($popup['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($popup['title_th']); ?>" 
                                     class="w-full h-full object-cover">
                            </a>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($popup['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($popup['title_th']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Navigation Arrows (Show only if > 1 popups) -->
            <?php if (count($validPopups) > 1): ?>
                <!-- Left Arrow -->
                <button type="button" 
                        onclick="prevPopupSlide()" 
                        class="absolute left-2.5 top-1/2 -translate-y-1/2 z-20 w-10 h-10 flex items-center justify-center rounded-full bg-black/40 hover:bg-black/75 text-white/90 hover:text-white backdrop-blur-sm shadow-md transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                <!-- Right Arrow -->
                <button type="button" 
                        onclick="nextPopupSlide()" 
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 z-20 w-10 h-10 flex items-center justify-center rounded-full bg-black/40 hover:bg-black/75 text-white/90 hover:text-white backdrop-blur-sm shadow-md transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <!-- Dots Indicator -->
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/40 backdrop-blur-md">
                    <?php foreach ($validPopups as $index => $popup): ?>
                        <span class="popup-dot w-2 h-2 rounded-full transition-all cursor-pointer <?php echo $index === 0 ? 'bg-white w-4' : 'bg-white/50'; ?>"
                              onclick="goToPopupSlide(<?php echo $index; ?>)"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Control Bar -->
        <div class="px-5 py-3.5 md:px-6 md:py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3 text-xs md:text-sm">
            <!-- Left: Don't show again today checkbox -->
            <label class="flex items-center gap-2 cursor-pointer text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white select-none">
                <input type="checkbox" id="dontShowPopupAgain" class="w-4 h-4 md:w-4.5 md:h-4.5 text-mcu-blue rounded border-gray-300 focus:ring-mcu-blue dark:bg-gray-700 dark:border-gray-600">
                <span class="font-medium">ไม่ต้องแสดงอีกในวันนี้</span>
            </label>

            <!-- Right: Action Button (Replaces "สถาบันวิจัยพุทธศาสตร์ มจร") -->
            <div id="popupActionButtonContainer">
                <a id="popupActionBtn" 
                   href="<?php echo htmlspecialchars($validPopups[0]['link_url'] ?? '#'); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="<?php echo empty($validPopups[0]['link_url']) ? 'hidden' : 'inline-flex'; ?> items-center justify-center px-4 py-1.5 md:px-5 md:py-2 text-xs md:text-sm font-bold text-white bg-mcu-blue hover:bg-blue-800 rounded-xl shadow-sm transition">
                    <span id="popupActionBtnText"><?php echo htmlspecialchars($validPopups[0]['link_text_th'] ?: 'ดูรายละเอียด'); ?></span>
                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
let currentPopupIndex = 0;
const totalPopupSlides = <?php echo count($validPopups); ?>;

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('siteAnnouncementModal');
    if (!modal || totalPopupSlides === 0) return;

    const popupKey = modal.getAttribute('data-popup-key');
    const storageKey = 'dismissed_popup_bundle_' + popupKey;
    const dismissedDate = localStorage.getItem(storageKey);
    const today = new Date().toISOString().split('T')[0];

    // Check if dismissed today
    if (dismissedDate === today) {
        return; // Do not show
    }

    // Show popup with slight delay for smooth page rendering
    setTimeout(() => {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            const dialog = modal.querySelector('div');
            if (dialog) dialog.classList.remove('scale-95');
        }, 30);
    }, 400);

    updateSlideUI(0);
});

function goToPopupSlide(index) {
    if (index < 0) index = totalPopupSlides - 1;
    if (index >= totalPopupSlides) index = 0;
    currentPopupIndex = index;
    updateSlideUI(index);
}

function nextPopupSlide() {
    goToPopupSlide(currentPopupIndex + 1);
}

function prevPopupSlide() {
    goToPopupSlide(currentPopupIndex - 1);
}

function updateSlideUI(index) {
    const slides = document.querySelectorAll('.popup-slide');
    const dots = document.querySelectorAll('.popup-dot');
    const actionBtn = document.getElementById('popupActionBtn');
    const actionBtnText = document.getElementById('popupActionBtnText');

    slides.forEach((slide, i) => {
        if (i === index) {
            slide.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
            slide.classList.add('opacity-100', 'z-10');

            // Update Action Button
            const link = slide.getAttribute('data-link');
            const linkText = slide.getAttribute('data-link-text') || 'ดูรายละเอียด';

            if (actionBtn && actionBtnText) {
                if (link && link.trim() !== '') {
                    actionBtn.href = link;
                    actionBtnText.textContent = linkText;
                    actionBtn.classList.remove('hidden');
                    actionBtn.classList.add('inline-flex');
                } else {
                    actionBtn.classList.add('hidden');
                    actionBtn.classList.remove('inline-flex');
                }
            }
        } else {
            slide.classList.remove('opacity-100', 'z-10');
            slide.classList.add('opacity-0', 'pointer-events-none', 'z-0');
        }
    });

    dots.forEach((dot, i) => {
        if (i === index) {
            dot.classList.add('bg-white', 'w-4');
            dot.classList.remove('bg-white/50', 'w-2');
        } else {
            dot.classList.remove('bg-white', 'w-4');
            dot.classList.add('bg-white/50', 'w-2');
        }
    });
}

function closeSitePopup(forceDismissToday) {
    const modal = document.getElementById('siteAnnouncementModal');
    if (!modal) return;

    const popupKey = modal.getAttribute('data-popup-key');
    const dontShow = document.getElementById('dontShowPopupAgain')?.checked || forceDismissToday;

    if (dontShow && popupKey) {
        const today = new Date().toISOString().split('T')[0];
        localStorage.setItem('dismissed_popup_bundle_' + popupKey, today);
    }

    // Smooth fade out
    modal.classList.add('opacity-0');
    const dialog = modal.querySelector('div');
    if (dialog) dialog.classList.add('scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
<?php endif; ?>
