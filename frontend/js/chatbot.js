// Animal Mart - AI Chatbot Floating Widget

document.addEventListener("DOMContentLoaded", () => {
    injectChatbot();
    setupChatbotEvents();
});

// Dynamic injection of chatbot widget to avoid duplicating HTML on all pages
function injectChatbot() {
    const chatContainer = document.createElement("div");
    chatContainer.className = "chat-widget no-print";
    chatContainer.innerHTML = `
        <button class="chat-button" id="chat-toggle-btn" aria-label="Toggle AI Assistant">
            <i class="bi bi-chat-dots-fill"></i>
        </button>
        <div class="chat-window glass-card" id="chat-window-el">
            <div class="chat-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-robot" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="m-0 fw-bold">Animal Mart AI</h6>
                        <small class="opacity-75" id="chat-status-text">Online Assistant</small>
                    </div>
                </div>
                <button class="btn-close btn-close-white" id="chat-close-btn"></button>
            </div>
            <div class="chat-body d-flex flex-column justify-content-between">
                <div id="chat-messages-container" style="flex: 1; overflow-y: auto; margin-bottom: 10px; max-height: 380px;">
                    <!-- Messages will appear here -->
                </div>
                <div class="chat-suggestions" id="chat-suggestions-container">
                    <!-- Suggestions will appear here -->
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" id="chat-input-el" placeholder="Type a message...">
                <button class="btn btn-primary-custom btn-sm rounded-circle d-flex align-items-center justify-content-center" id="chat-send-btn" style="width: 36px; height: 36px;">
                    <i class="bi bi-send-fill" style="font-size: 0.9rem;"></i>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(chatContainer);
}

function setupChatbotEvents() {
    const toggleBtn = document.getElementById("chat-toggle-btn");
    const closeBtn = document.getElementById("chat-close-btn");
    const sendBtn = document.getElementById("chat-send-btn");
    const inputEl = document.getElementById("chat-input-el");
    const chatWindow = document.getElementById("chat-window-el");

    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            const isVisible = chatWindow.style.display === "flex";
            chatWindow.style.display = isVisible ? "none" : "flex";
            if (!isVisible) {
                // Scroll to bottom
                scrollToBottom();
                // Send greeting if empty
                const container = document.getElementById("chat-messages-container");
                if (container.children.length === 0) {
                    sendGreeting();
                }
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            chatWindow.style.display = "none";
        });
    }

    if (sendBtn) {
        sendBtn.addEventListener("click", () => {
            handleUserMessage();
        });
    }

    if (inputEl) {
        inputEl.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                handleUserMessage();
            }
        });
    }

    // Listen for language changes from main.js
    window.addEventListener('langChanged', (e) => {
        // Clear messages and reset greeting
        const container = document.getElementById("chat-messages-container");
        container.innerHTML = "";
        sendGreeting();
    });
}

function sendGreeting() {
    const lang = localStorage.getItem("lang") || "en";
    let text = "";
    let chips = [];
    
    if (lang === "ta") {
        text = "வணக்கம்! நான் விலங்கு மார்ட் AI உதவியாளர். உங்களுக்கு இன்று நான் எவ்வாறு உதவ முடியும்? விலங்கு தீவனம், தடுப்பூசி, சிறந்த இனங்கள் மற்றும் விலைகளைப் பற்றி நீங்கள் கேட்கலாம்.";
        chips = ["உணவு முறை", "தடுப்பூசி அட்டவணை", "மாடு வகைகள்", "வாங்குவது எப்படி?"];
    } else {
        text = "Hello! I am your Animal Mart Assistant. How can I help you today? You can ask about animal feeding, vaccination schedules, breed recommendations, or price catalogs.";
        chips = ["Feeding Guide", "Vaccination Guide", "Cow Breeds", "How to buy?"];
    }

    appendMessage(text, "bot");
    renderSuggestions(chips);
}

function appendMessage(text, sender) {
    const container = document.getElementById("chat-messages-container");
    const msg = document.createElement("div");
    msg.className = `chat-message ${sender}`;
    
    if (sender === "bot") {
        msg.innerHTML = parseMarkdown(text);
    } else {
        msg.innerText = text;
    }
    
    container.appendChild(msg);
    scrollToBottom();
}

function renderSuggestions(chips) {
    const container = document.getElementById("chat-suggestions-container");
    container.innerHTML = "";
    chips.forEach(c => {
        const chip = document.createElement("span");
        chip.className = "chat-chip";
        chip.innerText = c;
        chip.addEventListener("click", () => {
            appendMessage(c, "user");
            sendBotRequest(c);
        });
        container.appendChild(chip);
    });
}

async function handleUserMessage() {
    const inputEl = document.getElementById("chat-input-el");
    const text = inputEl.value.trim();
    if (!text) return;

    appendMessage(text, "user");
    inputEl.value = "";
    
    await sendBotRequest(text);
}

async function sendBotRequest(messageText) {
    const lang = localStorage.getItem("lang") || "en";
    const statusText = document.getElementById("chat-status-text");
    
    if (statusText) statusText.innerText = lang === 'ta' ? "பதிலளிக்கிறது..." : "Typing...";

    try {
        const response = await fetch("../backend/api/chatbot.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                message: messageText,
                lang: lang
            })
        });

        const data = await response.json();
        
        if (data.status === "success") {
            appendMessage(data.reply, "bot");
            if (data.suggestions) {
                renderSuggestions(data.suggestions);
            }
        } else {
            appendMessage("Sorry, I encountered an issue processing your request.", "bot");
        }
    } catch (error) {
        console.error("Chatbot API error:", error);
        appendMessage("Connection error. Please try again.", "bot");
    } finally {
        if (statusText) statusText.innerText = lang === 'ta' ? "ஆன்லைன் உதவியாளர்" : "Online Assistant";
    }
}

function scrollToBottom() {
    const container = document.getElementById("chat-messages-container");
    container.scrollTop = container.scrollHeight;
}

// Simple regex markdown-to-HTML parser
function parseMarkdown(text) {
    let html = text;
    
    // Headers (### Header)
    html = html.replace(/^### (.*$)/gim, '<h6 class="fw-bold mt-2 mb-1">$1</h6>');
    html = html.replace(/^## (.*$)/gim, '<h5 class="fw-bold mt-2 mb-1">$1</h5>');
    html = html.replace(/^# (.*$)/gim, '<h4 class="fw-bold mt-2 mb-1">$1</h4>');
    
    // Bold (**text**)
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Line breaks
    html = html.replace(/\n/g, '<br>');
    
    // Bullet points (- Item)
    html = html.replace(/^\s*-\s*(.*$)/gim, '<div class="d-flex gap-2 align-items-start mb-1"><i class="bi bi-dot text-primary" style="font-size: 1.5rem; line-height: 1;"></i><span>$1</span></div>');
    
    return html;
}
