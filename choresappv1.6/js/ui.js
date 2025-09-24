// This file contains functions that manipulate the User Interface (UI).

import * as dom from './dom.js';

/**
 * Hides all pages and modals, then shows the specified page element.
 * @param {HTMLElement} pageElement The page element to make active.
 */
export function showPage(pageElement) {
    console.log("Showing page:", pageElement.id);
    document.querySelectorAll('.page').forEach(p => {
        p.classList.remove('active');
        p.classList.add('hidden');
    });

    pageElement.classList.remove('hidden');
    pageElement.classList.add('active');
    
    hideAllModals();
}

/**
 * Shows a specific modal. Can optionally leave other modals open.
 * @param {HTMLElement} modalToShow The modal element to show.
 * @param {boolean} [hideOthers=true] - If false, other modals will not be hidden.
 */
export function showModal(modalToShow, hideOthers = true) {
    console.log(`Attempting to show modal: ${modalToShow.id}, Hide Others: ${hideOthers}`);
    
    if (hideOthers) {
        hideAllModals(); 
    }

    if (modalToShow) {
        if (modalToShow.closest('#authSection')) {
            if (dom.authSection) dom.authSection.classList.remove('hidden');
        }
        modalToShow.classList.remove('hidden');
    }
}

/**
 * Hides all elements with the .modal class.
 */
export function hideAllModals() {
    console.log("Hiding all modals.");
    document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
    if (dom.authSection) dom.authSection.classList.add('hidden');
}

/**
 * Hides all modals specifically related to the authentication flow.
 */
export function hideAllAuthModals() {
    hideAllModals();
}

/**
 * Hides all parent dashboard sections and shows the one with the target ID.
 * @param {string} targetId The ID of the section to show.
 */
export function showParentSection(targetId) {
    console.log("Showing parent section:", targetId);

    if (dom.taskModal) dom.taskModal.classList.add('hidden');
    if (dom.rewardModal) dom.rewardModal.classList.add('hidden');
    if (dom.childModal) dom.childModal.classList.add('hidden');
    
    dom.parentSections.forEach(section => {
        section.classList.add('hidden');
        if (section.id === targetId + 'Section') {
            section.classList.remove('hidden');
        }
    });

    dom.parentNavLinks.forEach(link => {
        link.classList.remove('active-nav-link');
        if (link.dataset.target === targetId) {
            link.classList.add('active-nav-link');
        }
    });

    if (window.innerWidth < 768 && dom.parentSidebarNav && !dom.parentSidebarNav.classList.contains('hidden')) {
        dom.parentSidebarNav.classList.add('hidden');
    }
}

/**
 * Displays a custom message to the user in a modal.
 * @param {string} title The title of the message.
 * @param {string} text The body text of the message.
 * @param {'info'|'success'|'error'} type The type of message, for styling.
 */
export function showMessage(title, text, type = 'info') {
    console.log(`Message: ${title} - ${text} (Type: ${type})`);
    dom.messageModalTitle.textContent = title;
    dom.messageModalText.innerHTML = text;
    
    dom.messageModalTitle.classList.remove('text-red-500', 'text-lime-600', 'text-sky-600');
    dom.messageModal.querySelector('.modal-content').classList.add('text-center');
    
    if (type === 'error') {
        dom.messageModalTitle.classList.add('text-red-500');
    } else if (type === 'success') {
        dom.messageModalTitle.classList.add('text-lime-600');
    } else {
        dom.messageModalTitle.classList.add('text-sky-600');
    }

    showModal(dom.messageModal);
}
