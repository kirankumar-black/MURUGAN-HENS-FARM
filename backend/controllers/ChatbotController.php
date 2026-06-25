<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/middleware.php';

class ChatbotController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleMessage($data) {
        $data = sanitizeInput($data);
        $message = trim($data['message'] ?? '');
        $lang = $data['lang'] ?? 'en'; // 'en' or 'ta'

        if (empty($message)) {
            return [
                "status" => "error", 
                "message" => "Message cannot be empty."
            ];
        }

        // If OpenAI key is set, attempt to call OpenAI
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            $openaiResponse = $this->callOpenAI($message, $lang);
            if ($openaiResponse) {
                return [
                    "status" => "success",
                    "reply" => $openaiResponse['reply'],
                    "suggestions" => $openaiResponse['suggestions']
                ];
            }
        }

        // Fallback: Smart Local Multilingual NLP engine
        $localResponse = $this->processLocalNLP($message, $lang);
        return [
            "status" => "success",
            "reply" => $localResponse['reply'],
            "suggestions" => $localResponse['suggestions']
        ];
    }

    private function callOpenAI($message, $lang) {
        $systemPrompt = "You are a friendly and knowledgeable AI Assistant for 'Animal Mart', an online marketplace for buying and selling farm animals (Cows, Goats, Sheep, Chickens, Horses) and pets (Dogs, Cats, Rabbits, Fish, Birds). "
                      . "You assist users with: animal care, feeding guides, vaccination guides, breed recommendations, purchase help, and pricing details. "
                      . "Respond in the language the user speaks. If they speak Tamil, respond in Tamil. Otherwise, English. Keep answers concise, clear, and structured using markdown. "
                      . "Always suggest 3 follow-up short actions/questions.";

        $data = [
            "model" => OPENAI_MODEL,
            "messages" => [
                ["role" => "system", "content" => $systemPrompt],
                ["role" => "user", "content" => $message]
            ],
            "temperature" => 0.7
        ];

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . OPENAI_API_KEY
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $resArr = json_decode($response, true);
            if (isset($resArr['choices'][0]['message']['content'])) {
                $reply = $resArr['choices'][0]['message']['content'];
                return [
                    "reply" => $reply,
                    "suggestions" => ["How to buy?", "Vaccination Guide", "Best Cow Breed"]
                ];
            }
        }
        return null;
    }

    private function processLocalNLP($message, $lang) {
        $msg = strtolower($message);
        $reply = "";
        $suggestions = [];

        if ($lang === 'ta') {
            // Tamil Responses
            $suggestions = ["உணவு முறை", "தடுப்பூசி அட்டவணை", "மாடு வகைகள்", "வாங்குவது எப்படி?"];
            
            if ($this->containsAny($msg, ['வணக்கம்', 'ஹலோ', 'hi', 'hello'])) {
                $reply = "வணக்கம்! நான் விலங்கு மார்ட் AI உதவியாளர். உங்களுக்கு இன்று நான் எவ்வாறு உதவ முடியும்? விலங்கு தீவனம், தடுப்பூசி, சிறந்த இனங்கள் மற்றும் விலைகளைப் பற்றி நீங்கள் கேட்கலாம்.";
            } 
            else if ($this->containsAny($msg, ['உணவு', 'தீவனம்', 'சாப்பாடு', 'feed', 'food'])) {
                $reply = "### விலங்கு தீவன வழிகாட்டி 🌾\n\n"
                       . "1. **மாடுகள்/ஆடுகள்**: தினமும் 25-30 கிலோ பசுந்தீவனம், 5-8 கிலோ உலர்ந்த வைக்கோல் மற்றும் தாது உப்பு கலவை (Mineral mixture) கொடுக்க வேண்டும்.\n"
                       . "2. **கோழிகள்**: தானியங்கள் (கம்பு, சோளம்) மற்றும் முட்டையிடும் கோழிகளுக்கு கால்சியம் நிறைந்த தீவனம் வழங்க வேண்டும்.\n"
                       . "3. **நாய்கள்/பூனைகள்**: உலர் உணவுகள் மற்றும் வேகவைத்த மாமிசம் வழங்கலாம். சாக்லேட் அல்லது வெங்காயம் கொடுக்கக் கூடாது.";
            } 
            else if ($this->containsAny($msg, ['தடுப்பூசி', 'ஊசி', 'vaccine', 'vaccination'])) {
                $reply = "### தடுப்பூசி கால அட்டவணை 💉\n\n"
                       . "- **மாடுகள்**: கோமாரி நோய் (FMD) தடுப்பூசி வருடத்திற்கு இருமுறை போடப்பட வேண்டும்.\n"
                       . "- **ஆடுகள்**: பி.பி.ஆர் (PPR) மற்றும் ஆட்டுக்கொல்லி நோய் தடுப்பூசி ஆண்டுக்கு ஒருமுறை அவசியம்.\n"
                       . "- **நாய்கள்**: 6-8 வாரங்களில் DHPPi தடுப்பூசியும், 3 வது மாதத்தில் வெறிநாய் கடித்தல் (Anti-Rabies) தடுப்பூசியும் செலுத்த வேண்டும்.";
            } 
            else if ($this->containsAny($msg, ['மாடு', 'ஆடு', 'இனம்', 'breed', 'cow', 'goat'])) {
                $reply = "### சிறந்த இனப் பரிந்துரைகள் 🐄\n\n"
                       . "- **அதிக பால் தரும் மாடுகள்**: கிர் (Gir), சாஹிவால் (Sahiwal), எச்.எஃப் (Holstein Friesian).\n"
                       . "- **இறைச்சிக்கான சிறந்த ஆடுகள்**: ஜமுனாபாரி (Jamunapari), தலைச்சேரி (Thalassery), போயர் (Boer).\n"
                       . "- **சிறந்த காவல் நாய்கள்**: ஜெர்மன் ஷெப்பர்ட், ராட்வீலர், ராஜபாளையம்.";
            } 
            else if ($this->containsAny($msg, ['விலை', 'விற்பனை', 'வாங்க', 'price', 'buy', 'sell', 'cost'])) {
                // Fetch average price of available animals
                $priceInfo = $this->getLocalAveragePrices();
                $reply = "### விலங்கு மார்ட் விலை நிலவரம் 💰\n\n" . $priceInfo . "\n\nஎங்கள் 'விலங்குகள்' பக்கத்திற்குச் சென்று நீங்கள் விரும்பும் விலங்கை வாங்கலாம்!";
            } 
            else {
                $reply = "மன்னிக்கவும், தங்களின் கேள்வி எனக்கு புரியவில்லை. மாடுகளின் உணவு முறை, தடுப்பூசிகள், இனங்கள் அல்லது விலங்கு மார்ட்டில் வாங்கும் வழிகளைப் பற்றி கேளுங்கள்.";
            }
        } else {
            // English Responses
            $suggestions = ["Feeding Guide", "Vaccination Guide", "Cow Breeds", "How to buy?"];

            if ($this->containsAny($msg, ['hello', 'hi', 'hey', 'greetings'])) {
                $reply = "Hello! I am your Animal Mart Assistant. How can I help you today? You can ask about animal feeding, vaccination schedules, breed recommendations, or price catalogs.";
            } 
            else if ($this->containsAny($msg, ['feed', 'food', 'diet', 'eating'])) {
                $reply = "### Animal Feeding & Nutrition Guide 🌾\n\n"
                       . "1. **Cows & Goats**: Require 70% green fodder (Napier grass, Co4), 25% dry fodder (hay), and 5% concentrate feeds with mineral mixtures. Provide clean water at all times.\n"
                       . "2. **Chickens**: Use starter mash for chicks, grower feeds, and layer feeds for egg-producing hens. Add grit for digestion.\n"
                       . "3. **Dogs & Cats**: High-quality protein diet. Avoid feeding chocolate, grapes, garlic, or cooked bones.";
            } 
            else if ($this->containsAny($msg, ['vaccine', 'vaccination', 'injection', 'immunization'])) {
                $reply = "### Vaccination Schedule Guidelines 💉\n\n"
                       . "- **Cows**: Foot and Mouth Disease (FMD) vaccine once every 6 months. Anthrax & Hemorrhagic Septicemia vaccine annually.\n"
                       . "- **Goats & Sheep**: PPR (Peste des Petits Ruminants) and Enterotoxemia vaccine annually before monsoons.\n"
                       . "- **Dogs**: DHPPi (Distemper, Hepatitis, Parvovirus) vaccine at 6-8 weeks, booster at 12 weeks. Anti-Rabies vaccine at 3 months, repeated annually.";
            } 
            else if ($this->containsAny($msg, ['recommend', 'suggest', 'breed', 'type', 'choice'])) {
                $reply = "### Top Breed Recommendations 🐾\n\n"
                       . "- **Dairy Cows**: Gir and Sahiwal (high fat indigenous milk), Holstein Friesian (highest volume).\n"
                       . "- **Meat Goats**: Boer (rapid growth weight), Jamunapari (dual milk/meat), Thalassery.\n"
                       . "- **Family Pets**: Golden Retriever (friendly, trainable), Persian Cat (calm, indoor).";
            } 
            else if ($this->containsAny($msg, ['price', 'cost', 'buy', 'sell', 'purchase', 'shop'])) {
                $priceInfo = $this->getLocalAveragePrices();
                $reply = "### Animal Mart Pricing Directory 💰\n\n" . $priceInfo . "\n\nNavigate to our **Animals** page to search and filter specific listings near your location!";
            } 
            else {
                $reply = "I'm sorry, I didn't quite get that. Could you please ask about animal feeding, vaccinations, breed recommendations, or shopping details?";
            }
        }

        return [
            "reply" => $reply,
            "suggestions" => $suggestions
        ];
    }

    private function containsAny($str, array $arr) {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getLocalAveragePrices() {
        try {
            $query = "SELECT c.name, MIN(a.price) as min_p, MAX(a.price) as max_p, AVG(a.price) as avg_p 
                      FROM animals a
                      JOIN categories c ON a.category_id = c.id
                      WHERE a.status = 'available'
                      GROUP BY c.id";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $prices = $stmt->fetchAll();

            if (empty($prices)) {
                return "Currently, no listings are available. Please check back later.";
            }

            $res = "";
            foreach ($prices as $p) {
                $res .= "- **{$p['name']}**: Min ₹" . number_format($p['min_p']) . " - Max ₹" . number_format($p['max_p']) . " (Avg ₹" . number_format($p['avg_p'], 0) . ")\n";
            }
            return $res;
        } catch (Exception $e) {
            return "- Cows: Avg ₹65,000\n- Goats: Avg ₹12,000\n- Dogs: Avg ₹20,000";
        }
    }
}
?>
