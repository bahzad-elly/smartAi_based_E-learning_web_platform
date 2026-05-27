/**
 * Smart E-Learning Web Platform - Asynchronous AJAX Authentication Controller
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Toast Notification Helper
    const showNotification = (message, type = "success") => {
        // Remove existing alerts if any
        const existingToast = document.querySelector(".custom-toast");
        if (existingToast) existingToast.remove();

        const toast = document.createElement("div");
        toast.className = `custom-toast ${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: ${type === 'success' ? '#2ecc71' : '#e74c3c'};
            color: #fff;
            font-size: 1.6rem;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        `;
        toast.innerText = message;
        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0)";
        }, 50);

        // Animate out
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(-20px)";
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // Helper: Show Spinner Loading
    const toggleSpinner = (form, state = true) => {
        const btn = form.querySelector('input[type="submit"]');
        if (!btn) return;
        if (state) {
            btn.dataset.originalText = btn.value;
            btn.value = "Processing...";
            btn.disabled = true;
            btn.style.opacity = "0.7";
        } else {
            btn.value = btn.dataset.originalText || "Submit";
            btn.disabled = false;
            btn.style.opacity = "1";
        }
    };

    // 2. Intercept Form Submissions
    const forms = ["#login-form", "#register-form", "#forgot-form", "#reset-form"];
    forms.forEach(selector => {
        const form = document.querySelector(selector);
        if (!form) return;

        form.addEventListener("submit", (e) => {
            e.preventDefault();

            // Client-side validations
            const pass = form.querySelector("#pass");
            const cpass = form.querySelector("#cpass");
            if (pass && cpass && pass.value !== cpass.value) {
                showNotification("Confirm password does not match!", "error");
                return;
            }

            toggleSpinner(form, true);

            const formData = new FormData(form);

            fetch(form.action || window.location.href, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                toggleSpinner(form, false);
                if (data.status === "success") {
                    showNotification(data.message, "success");
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showNotification(data.message, "error");
                }
            })
            .catch(err => {
                toggleSpinner(form, false);
                console.error("Auth Request Error:", err);
                showNotification("An unexpected connection error occurred.", "error");
            });
        });
    });

    // 3. Mock Google OAuth Authentication Flow
    const googleBtn = document.querySelector("#google-login-btn");
    if (googleBtn) {
        googleBtn.addEventListener("click", () => {
            // Render Google OAuth Simulated modal
            const modal = document.createElement("div");
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0,0,0,0.6);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
                font-family: Arial, sans-serif;
            `;
            modal.innerHTML = `
                <div style="background-color: #fff; width: 400px; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); text-align: center; position: relative;">
                    <i class="fab fa-google" style="font-size: 3.5rem; color: #db4437; margin-bottom: 15px;"></i>
                    <h2 style="font-size: 2.2rem; color: #333; margin-bottom: 5px;">Sign in with Google</h2>
                    <p style="font-size: 1.4rem; color: #666; margin-bottom: 25px;">to continue to Educa Platform</p>
                    
                    <!-- Simulated Account Select list -->
                    <div style="border-top: 1px solid #eee; text-align: left; padding: 10px 0; max-height: 200px; overflow-y: auto;">
                        <div class="google-acc" style="display: flex; align-items: center; padding: 12px; cursor: pointer; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f5f5f5'" onmouseout="this.style.backgroundColor='transparent'">
                            <div style="width: 35px; height: 35px; border-radius: 50%; background-color: #1a73e8; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: bold; margin-right: 15px;">A</div>
                            <div>
                                <div style="font-size: 1.4rem; font-weight: bold; color: #3c4043;">Alex Mercer (Student)</div>
                                <div style="font-size: 1.2rem; color: #5f6368;">alex.mercer@gmail.com</div>
                            </div>
                        </div>
                        <div class="google-acc" style="display: flex; align-items: center; padding: 12px; cursor: pointer; border-radius: 4px; transition: background 0.2s; margin-top: 5px;" onmouseover="this.style.backgroundColor='#f5f5f5'" onmouseout="this.style.backgroundColor='transparent'">
                            <div style="width: 35px; height: 35px; border-radius: 50%; background-color: #34a853; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: bold; margin-right: 15px;">T</div>
                            <div>
                                <div style="font-size: 1.4rem; font-weight: bold; color: #3c4043;">Tutor Ken (Instructor)</div>
                                <div style="font-size: 1.2rem; color: #5f6368;">ken.instructor@gmail.com</div>
                            </div>
                        </div>
                    </div>
                    
                    <button id="close-oauth" style="margin-top: 20px; padding: 8px 16px; background-color: #f1f3f4; color: #3c4043; border: none; border-radius: 4px; font-size: 1.4rem; cursor: pointer;">Cancel</button>
                </div>
            `;
            document.body.appendChild(modal);

            // Event Listeners inside modal
            modal.querySelector("#close-oauth").addEventListener("click", () => modal.remove());

            modal.querySelectorAll(".google-acc").forEach((accEl, idx) => {
                accEl.addEventListener("click", () => {
                    modal.innerHTML = `
                        <div style="background-color: #fff; width: 300px; padding: 40px 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); text-align: center;">
                            <div style="border: 4px solid #f3f3f3; border-top: 4px solid #db4437; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                            <h3 style="font-size: 1.8rem; color: #333; margin-bottom: 5px;">Authenticating...</h3>
                            <p style="font-size: 1.3rem; color: #666;">Establishing secure OAuth session</p>
                        </div>
                        <style>
                            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                        </style>
                    `;

                    // Generate temporary simulated session injection
                    setTimeout(() => {
                        modal.remove();
                        showNotification("Simulated Google Login Successful!", "success");
                        setTimeout(() => {
                            // Student redirect vs Tutor redirect
                            window.location.href = (idx === 0) ? "home.php" : "admin/dashboard.php";
                        }, 1500);
                    }, 2000);
                });
            });
        });
    }
});
