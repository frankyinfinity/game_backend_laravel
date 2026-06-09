#!/usr/bin/env python3
"""
Generatore locale di pixel art 32x32 usando Stable Diffusion.
Uso: python local_pixel_gen.py "prompt text" [size]
Output: JSON con matrice di pixel (hex color o null per trasparente).
"""

import sys
import json
import os
import warnings
warnings.filterwarnings("ignore")

# Dizionario base italiano -> inglese per prompt comuni di giochi
IT_TO_EN = {
    "spada": "sword", "spada rossa": "red sword",
    "scudo": "shield", "scudo blu": "blue shield",
    "drago": "dragon", "drago verde": "green dragon",
    "cuore": "heart", "cuore rosso": "red heart",
    "stella": "star", "stella gialla": "yellow star",
    "albero": "tree", "albero verde": "green tree",
    "casa": "house", "castello": "castle",
    "ninja": "ninja", "guerriero": "warrior",
    "mag0": "mage", "strega": "witch",
    "fata": "fairy", "scheletro": "skeleton",
    "teschio": "skull", "fuoco": "fire",
    "acqua": "water", "fulmine": "lightning",
    "pozione": "potion", "arma": "weapon",
    "anello": "ring", "corona": "crown",
    "chiave": "key", "moneta": "coin",
    "cristallo": "crystal", "gemma": "gem",
    "arco": "bow", "freccia": "arrow",
    "ascia": "axe", "martello": "hammer",
    "pugnale": "dagger", " bastone": "staff",
    "elmo": "helmet", "armatura": "armor",
    "vestito": "dress", "cappello": "hat",
    "pantaloni": "pants", "scarpe": "shoes",
    "gatto": "cat", "cane": "dog",
    "lupo": "wolf", "orso": "bear",
    "leone": "lion", "tigre": "tiger",
    "coniglio": "rabbit", "uccello": "bird",
    "pesce": "fish", "serpente": "snake",
    "ragno": "spider", "farfalla": "butterfly",
    "mela": "apple", "banana": "banana",
    "fungo": "mushroom", "fiore": "flower",
    "sole": "sun", "luna": "moon",
    "nuvola": "cloud", "montagna": "mountain",
    "isola": "island", "nave": "ship",
    "barca": "boat", "aereo": "airplane",
    "auto": "car", "treno": "train",
    "pirata": "pirate", "robot": "robot",
    "alieno": "alien", "zombie": "zombie",
    "goblin": "goblin", "orco": "orc",
    "elfo": "elf", "nano": "dwarf",
    "fantasma": "ghost", "vampiro": "vampire",
    "lancia": "spear", "bomby": "bomb",
    "palla": "ball", "libro": "book",
    "borsa": "bag", "cassa": "chest",
    "porta": "door", "finestra": "window",
    "torcia": "torch", "candela": "candle",
    "nave spaziale": "spaceship", "robot": "robot",
}


def translate_prompt(text):
    """Traduce prompt italiano in inglese per migliori risultati con SD."""
    text_lower = text.lower().strip()

    # Cerca corrispondenza esatta
    if text_lower in IT_TO_EN:
        return IT_TO_EN[text_lower]

    # Cerca corrispondenze parziali
    result = text_lower
    for it, en in sorted(IT_TO_EN.items(), key=lambda x: -len(x[0])):
        if it in result:
            result = result.replace(it, en)

    # Se non ha tradotto nulla, usa il testo originale
    if result == text_lower:
        return text  # SD capisce anche italiano basico

    return result


def quantize_colors(img, max_colors=16):
    """Riduci i colori dell'immagine a un numero massimo (pixel art style)."""
    return img.quantize(colors=max_colors, method=2).convert("RGB")


def extract_dominant_background(img):
    """Rileva il colore di sfondo (angoli)."""
    w, h = img.size
    pixels = img.load()
    corners = []
    for pos in [(0, 0), (w-1, 0), (0, h-1), (w-1, h-1), (w//2, 0), (0, h//2)]:
        corners.append(pixels[pos])
    from collections import Counter
    color_count = Counter(tuple(c[:3]) for c in corners)
    return color_count.most_common(1)[0][0]


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Manca il prompt"}))
        sys.exit(1)

    prompt_it = sys.argv[1]
    output_size = int(sys.argv[2]) if len(sys.argv) > 2 else 32

    # Traduci prompt italiano -> inglese
    prompt_en = translate_prompt(prompt_it)

    try:
        import torch
        from diffusers import StableDiffusionPipeline, DPMSolverMultistepScheduler
        from PIL import Image

        model_id = "runwayml/stable-diffusion-v1-5"

        print(f"Caricamento modello {model_id}...", file=sys.stderr)

        pipe = StableDiffusionPipeline.from_pretrained(
            model_id,
            torch_dtype=torch.float32,
            safety_checker=None,
            requires_safety_checker=False,
        )
        pipe.scheduler = DPMSolverMultistepScheduler.from_config(pipe.scheduler.config)
        pipe = pipe.to("cpu")
        pipe.enable_attention_slicing()

        print(f"Prompt: {prompt_en} (da: {prompt_it})", file=sys.stderr)

        # Prompt ottimizzato per pixel art
        enhanced_prompt = (
            f"pixel art, {prompt_en}, "
            f"centered on solid white background, "
            f"flat colors, 16-bit style, "
            f"game sprite, clean sharp edges, "
            f"bold colors, simple iconic design"
        )

        # Genera a 256x256
        image = pipe(
            enhanced_prompt,
            num_inference_steps=40,
            guidance_scale=8.0,
            width=256,
            height=256,
        ).images[0]

        print("Post-processing...", file=sys.stderr)

        # 1. Rileva sfondo
        bg_color = extract_dominant_background(image)

        # 2. Quantizza colori
        image = quantize_colors(image, max_colors=20)

        # 3. Ridimensiona a 32x32
        pixel_img = image.resize((output_size, output_size), Image.LANCZOS)

        # 4. Riquantizza dopo resize
        pixel_img = quantize_colors(pixel_img, max_colors=16)

        # 5. Rimuovi sfondo (solo bianco puro e colore sfondo)
        from colorsys import rgb_to_hsv
        final_img = pixel_img.convert("RGBA")
        fp = final_img.load()

        for y in range(output_size):
            for x in range(output_size):
                r, g, b, a = fp[x, y]
                h, s, v = rgb_to_hsv(r/255, g/255, b/255)
                # Solo sfondo bianco o vicino al colore rilevato
                is_bg = (
                    (r > 240 and g > 240 and b > 240) or
                    (abs(r - bg_color[0]) < 20 and abs(g - bg_color[1]) < 20 and abs(b - bg_color[2]) < 20)
                )
                if is_bg:
                    fp[x, y] = (0, 0, 0, 0)

        # Estrai pixel
        pixels = []
        for y in range(output_size):
            row = []
            for x in range(output_size):
                r, g, b, a = fp[x, y]
                if a < 100:
                    row.append(None)
                else:
                    row.append(f"#{r:02X}{g:02X}{b:02X}")
            pixels.append(row)

        result = {
            "pixels": pixels,
            "model": "local-stable-diffusion-v1-5",
            "prompt": prompt_it,
        }
        print(json.dumps(result))

    except ImportError as e:
        print(json.dumps({"error": f"Dipendenza mancante: {str(e)}"}))
        sys.exit(1)
    except Exception as e:
        print(json.dumps({"error": f"Errore generazione: {str(e)}"}))
        sys.exit(1)


if __name__ == "__main__":
    main()
