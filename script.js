document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('addPumpModal');
    const openModalBtns = document.querySelectorAll('#mainAddBtn, #navPlusIcon');
    const closeModalBtns = document.querySelectorAll('#closeModalBtn, #closeModalCross');

    // Open Modal
    openModalBtns.forEach(btn => {
        btn?.addEventListener('click', (e) => {
            e.preventDefault();
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; 
            } else {
                window.location.href = 'index.php?add=1';
            }
        });
    });

    // Close Modal
    const closeModal = () => {
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = 'auto'; 
    };

    closeModalBtns.forEach(btn => btn?.addEventListener('click', closeModal));

    // Update Modal Logic
    const updateModal = document.getElementById('updatePumpModal');
    const closeUpdateModalBtns = document.querySelectorAll('#closeUpdateModalBtn, #closeUpdateModalCross');

    // Event Delegation for dynamically loaded update buttons
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.update-btn, .open-update-modal');
        if (btn) {
            document.getElementById('updatePumpId').value = btn.getAttribute('data-id');
            document.getElementById('updatePumpName').value = btn.getAttribute('data-name');
            document.getElementById('updateHasFuel').checked = btn.getAttribute('data-has-fuel') === '1';
            document.getElementById('updatePetrol').checked = btn.getAttribute('data-petrol') === '1';
            document.getElementById('updateOctane').checked = btn.getAttribute('data-octane') === '1';
            document.getElementById('updateDiesel').checked = btn.getAttribute('data-diesel') === '1';
            if(document.getElementById('updateFuelLimit')) {
                document.getElementById('updateFuelLimit').value = btn.getAttribute('data-fuel-limit') || '';
            }
            
            // Trigger change event manually to update labels accurately
            ['updateHasFuel', 'updatePetrol', 'updateOctane', 'updateDiesel'].forEach(id => {
                const toggle = document.getElementById(id);
                if(toggle) toggle.dispatchEvent(new Event('change'));
            });
            
            if (updateModal) {
                updateModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

    // Like/Dislike বাটনে ক্লিক করলে পেজ রিফ্রেশ হওয়া বন্ধ করার লজিক
    const voteBtn = e.target.closest('.vote-btn');
    if (voteBtn) {
        e.preventDefault(); // এটি পেজ রিফ্রেশ হওয়া বন্ধ করবে
        
        const form = voteBtn.closest('form');
        if (!form) return;

        const formData = new FormData(form);
        voteBtn.style.opacity = '0.5';
        voteBtn.style.pointerEvents = 'none';

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            
            // আপডেট হওয়া লাইক/ডিসলাইক কাউন্ট বের করে বসানো
            const pumpId = formData.get('pump_id');
            const currentFeedback = form.closest('.feedback-actions');
            const newPumpInput = doc.querySelector(`input[name="pump_id"][value="${pumpId}"]`);
            
            if (currentFeedback && newPumpInput) {
                const newFeedback = newPumpInput.closest('.feedback-actions');
                const currentLikeSpan = currentFeedback.querySelector('.vote-like span');
                const currentDislikeSpan = currentFeedback.querySelector('.vote-dislike span');
                const newLikeSpan = newFeedback.querySelector('.vote-like span');
                const newDislikeSpan = newFeedback.querySelector('.vote-dislike span');
                
                if (currentLikeSpan && newLikeSpan) currentLikeSpan.innerText = newLikeSpan.innerText;
                if (currentDislikeSpan && newDislikeSpan) currentDislikeSpan.innerText = newDislikeSpan.innerText;
            }

            // বাটন আবার নরমাল করা
            voteBtn.style.opacity = '1';
            voteBtn.style.pointerEvents = 'auto';

            // সার্ভার থেকে আসা টোস্ট মেসেজ দেখানো (যেমন: লিমিট শেষ, বা ভোট দেওয়া হয়েছে)
            const serverToast = doc.getElementById('toast');
            const localToast = document.getElementById('toast');
            if (localToast) {
                localToast.innerHTML = serverToast ? serverToast.innerHTML : '<i class="fa-solid fa-circle-check"></i> মতামত গ্রহণ করা হয়েছে!';
                localToast.classList.add('show');
                setTimeout(() => { localToast.classList.remove('show'); }, 3000);
            }
        })
        .catch(err => {
            voteBtn.style.opacity = '1';
            voteBtn.style.pointerEvents = 'auto';
        });
    }
    });

    const closeUpdateModal = () => {
        if (updateModal) updateModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    };

    closeUpdateModalBtns.forEach(btn => btn?.addEventListener('click', closeUpdateModal));

    // Global Modal Events
    window.addEventListener('click', (e) => { 
        if (modal && e.target === modal) closeModal(); 
        if (updateModal && e.target === updateModal) closeUpdateModal(); 
    });
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape') { 
            if (modal && modal.classList.contains('active')) closeModal(); 
            if (updateModal && updateModal.classList.contains('active')) closeUpdateModal(); 
        } 
    });

    // Dynamic Toggle Switches Logic
    const toggleInputs = document.querySelectorAll('.dynamic-toggle');
    toggleInputs.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const textId = this.getAttribute('data-text-id');
            const onText = this.getAttribute('data-on');
            const offText = this.getAttribute('data-off');
            const textElem = document.getElementById(textId);
            
            if(textElem) {
                textElem.innerHTML = this.checked ? onText : offText;
            }

            // Highlight Box Colors
            const parentBox = this.closest('.highlight-box');
            if(parentBox) {
                if(this.checked) {
                    parentBox.style.background = 'var(--success-bg)';
                    parentBox.style.borderColor = '#bbf7d0';
                    if(textElem) textElem.style.color = 'var(--success-text)';
                } else {
                    parentBox.style.background = 'var(--danger-bg)';
                    parentBox.style.borderColor = '#fecdd3';
                    if(textElem) textElem.style.color = 'var(--danger-text)';
                }
            } else if(textElem) {
                textElem.style.color = this.checked ? 'var(--primary)' : 'var(--text-muted)';
            }
        });
        
        // Init default state
        toggle.dispatchEvent(new Event('change'));
    });

    // Fuel Dependency Logic (Disable specific fuels if 'has_fuel' is false)
    const handleFuelDependency = (mainId, dependentIds) => {
        const mainToggle = document.getElementById(mainId);
        if (!mainToggle) return;
        
        const updateDependencies = () => {
            const isChecked = mainToggle.checked;
            dependentIds.forEach(id => {
                const toggle = document.getElementById(id);
                if (toggle) {
                    const container = toggle.closest('.toggle-container');
                    if (!isChecked) {
                        if (toggle.checked) {
                            toggle.checked = false;
                            toggle.dispatchEvent(new Event('change'));
                        }
                        if (container) container.classList.add('disabled');
                    } else {
                        if (container) container.classList.remove('disabled');
                    }
                }
            });
        };
        mainToggle.addEventListener('change', updateDependencies);
        updateDependencies();
    };
    
    handleFuelDependency('addHasFuel', ['addPetrol', 'addOctane', 'addDiesel']);
    handleFuelDependency('updateHasFuel', ['updatePetrol', 'updateOctane', 'updateDiesel']);

    // Filtering Logic
    const pills = document.querySelectorAll('.pill');

    let currentFilter = 'all';
    
    const applyFilter = () => {
        const currentPumpCards = document.querySelectorAll('.pump-card');
        const currentEmptyState = document.querySelector('.empty-state');
        let visibleCount = 0;
        currentPumpCards.forEach(card => {
            let showCard = false;
            if (currentFilter === 'all') showCard = true;
            else if (currentFilter === 'has-fuel' && card.getAttribute('data-has-fuel') === '1') showCard = true;
            else if (currentFilter === 'no-fuel' && card.getAttribute('data-has-fuel') === '0') showCard = true;
            else if (currentFilter === 'petrol' && card.getAttribute('data-petrol') === '1') showCard = true;
            else if (currentFilter === 'octane' && card.getAttribute('data-octane') === '1') showCard = true;
            else if (currentFilter === 'diesel' && card.getAttribute('data-diesel') === '1') showCard = true;
            card.style.display = showCard ? 'block' : 'none';
            if (showCard) visibleCount++;
        });
        if (currentEmptyState) currentEmptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
    };

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentFilter = pill.getAttribute('data-filter');
            applyFilter();
        });
    });

    // AJAX Refresh Logic
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            const originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '⏳ হচ্ছে...';
            refreshBtn.style.pointerEvents = 'none';
            
            fetch(window.location.pathname)
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newPumpList = doc.querySelector('.pump-list');
                    if (newPumpList) {
                        document.querySelector('.pump-list').innerHTML = newPumpList.innerHTML;
                        applyFilter();
                    }
                    refreshBtn.innerHTML = originalHTML;
                    refreshBtn.style.pointerEvents = 'auto';
                })
                .catch(() => {
                    refreshBtn.innerHTML = originalHTML;
                    refreshBtn.style.pointerEvents = 'auto';
                });
        });
    }

    // Map Help Toggle
    const mapHelpToggle = document.getElementById('mapHelpToggle');
    const mapHelpContent = document.getElementById('mapHelpContent');
    if (mapHelpToggle && mapHelpContent) {
        mapHelpToggle.addEventListener('click', (e) => {
            e.preventDefault();
            mapHelpContent.classList.toggle('show');
        });
    }

    // File Upload Label Status Change
    const fileInput = document.getElementById('pumpImage');
    const fileLabel = document.getElementById('fileLabel');
    if(fileInput && fileLabel) {
        fileInput.addEventListener('change', function() {
            if(this.files && this.files.length > 0) {
                fileLabel.innerHTML = "<i class='fa-solid fa-circle-check'></i> ছবি নির্বাচন করা হয়েছে (" + this.files[0].name + ")";
                fileLabel.style.borderColor = "var(--success)";
                fileLabel.style.color = "var(--success)";
                fileLabel.style.background = "var(--success-bg)";
            } else {
                fileLabel.innerHTML = "<i class='fa-solid fa-camera'></i> ছবি তুলুন বা গ্যালারি থেকে বাছাই করুন";
                fileLabel.style.borderColor = "";
                fileLabel.style.color = "";
                fileLabel.style.background = "";
            }
        });
    }

    // File Upload Label Status Change for Update Modal
    const updateFileInput = document.getElementById('updatePumpImage');
    const updateFileLabel = document.getElementById('updateFileLabel');
    if(updateFileInput && updateFileLabel) {
        updateFileInput.addEventListener('change', function() {
            if(this.files && this.files.length > 0) {
                updateFileLabel.innerHTML = "<i class='fa-solid fa-circle-check'></i> ছবি নির্বাচন করা হয়েছে (" + this.files[0].name + ")";
                updateFileLabel.style.borderColor = "var(--success)";
                updateFileLabel.style.color = "var(--success)";
                updateFileLabel.style.background = "var(--success-bg)";
            } else {
                updateFileLabel.innerHTML = "<i class='fa-solid fa-camera'></i> ছবি তুলুন বা বাছাই করুন";
                updateFileLabel.style.borderColor = "";
                updateFileLabel.style.color = "";
                updateFileLabel.style.background = "";
            }
        });
    }

    // Auto-hide Toast Notification
    const toast = document.getElementById('toast');
    if (toast) {
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    }

    // URL এ add=1 থাকলে স্বয়ংক্রিয়ভাবে Add Modal ওপেন করা
    if (window.location.search.includes('add=1') && modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});