#!/usr/bin/env python3
import argparse
import os
import sys

from PIL import Image

try:
    from rembg import remove
    REMBG_AVAILABLE = True
except ImportError:
    REMBG_AVAILABLE = False


def estimate_background_color(image):
    width, height = image.size
    samples = []
    pixels = image.load()
    for x in range(width):
        samples.append(pixels[x, 0][:3])
        samples.append(pixels[x, height - 1][:3])
    for y in range(height):
        samples.append(pixels[0, y][:3])
        samples.append(pixels[width - 1, y][:3])

    counts = {}
    for color in samples:
        counts[color] = counts.get(color, 0) + 1

    return max(counts, key=counts.get)


def color_distance(a, b):
    return sum((a[i] - b[i]) ** 2 for i in range(3)) ** 0.5


def remove_background_simple(image, threshold=30):
    image = image.convert("RGBA")
    bg_color = estimate_background_color(image)
    pixels = image.load()
    width, height = image.size

    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if a == 0:
                continue
            if color_distance((r, g, b), bg_color) <= threshold:
                pixels[x, y] = (r, g, b, 0)

    return image


def process_image(input_path, output_path, threshold=30, use_rembg=False):
    with Image.open(input_path) as img:
        if use_rembg and REMBG_AVAILABLE:
            output = remove(img)
            output.save(output_path)
        else:
            output = remove_background_simple(img, threshold=threshold)
            output.save(output_path)


def parse_args():
    parser = argparse.ArgumentParser(description="Usuń tło ze zdjęcia.")
    parser.add_argument("input", help="Ścieżka do wejściowego pliku obrazu")
    parser.add_argument("output", help="Ścieżka do wyjściowego pliku PNG")
    parser.add_argument("--threshold", type=int, default=30, help="Próg dopasowania koloru tła (domyślnie 30)")
    parser.add_argument("--rembg", action="store_true", help="Użyj rembg jeśli jest dostępny")
    return parser.parse_args()


def main():
    args = parse_args()
    if args.rembg and not REMBG_AVAILABLE:
        print("Pakiet rembg nie jest zainstalowany. Użyj 'pip install rembg' lub uruchom bez --rembg.")
        sys.exit(1)

    if not os.path.isfile(args.input):
        print(f"Plik wejściowy nie istnieje: {args.input}")
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(args.output)), exist_ok=True)
    process_image(args.input, args.output, threshold=args.threshold, use_rembg=args.rembg)
    print(f"Zapisano wynik do: {args.output}")


if __name__ == "__main__":
    main()
