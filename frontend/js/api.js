// Animal Mart - Secure API Client

const API_BASE_URL = '../backend/api';

async function apiFetch(endpoint, options = {}) {
    const url = `${API_BASE_URL}/${endpoint}`;
    
    // Setup default headers
    options.headers = options.headers || {};
    
    // Inject JWT token if exists in local storage
    const token = localStorage.getItem("token");
    if (token) {
        options.headers["Authorization"] = `Bearer ${token}`;
    }
    
    // Set content type to JSON by default unless it is FormData (for uploads)
    if (!(options.body instanceof FormData) && !options.headers["Content-Type"]) {
        options.headers["Content-Type"] = "application/json";
    }

    try {
        const response = await fetch(url, options);
        
        // Handle token expiration / unauthorized requests
        if (response.status === 401) {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            
            // Redirect to login only if not already on login/register pages
            const currentPath = window.location.pathname;
            if (!currentPath.includes("login.html") && !currentPath.includes("register.html") && !currentPath.includes("index.html")) {
                showToast("Session expired. Please login again.", "error");
                setTimeout(() => {
                    window.location.href = "login.html";
                }, 1500);
            }
            return { status: "error", message: "Unauthorized access." };
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("API Request Error:", error);
        return { status: "error", message: "Network connection error. Please try again." };
    }
}
