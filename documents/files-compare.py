## Files Compare
# Last update: 2026-07-27

"""
Compare a reference PDF against every other PDF in a folder (recursively), and print a similarity score (0-100%) for each, sorted highest first.
"""

import difflib
import glob
import os
import sys

import pdfplumber
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


def extract_text(path):
    try:
        with pdfplumber.open(path) as pdf:
            return ' '.join(page.extract_text() or '' for page in pdf.pages)
    except Exception as e:
        print(f'  [warning] could not read {path}: {e}', file=sys.stderr)
        return ''


def overlap_display(reference_path, candidate_path, min_words=8):
    """
    Print the actual text passages shared between two PDFs.
    min_words: ignore matches shorter than this (avoids noise from common short phrases)
    """
    ref_text = extract_text(reference_path)
    other_text = extract_text(candidate_path)

    ref_words = ref_text.split()
    other_words = other_text.split()

    matcher = difflib.SequenceMatcher(None, ref_words, other_words, autojunk=False)
    blocks = [b for b in matcher.get_matching_blocks() if b.size >= min_words]

    if not blocks:
        print(f'No shared passages of {min_words}+ words found.')
        return

    print(f'Reference: {reference_path}')
    print(f'Other:     {candidate_path}')
    print(f'Found {len(blocks)} shared passage(s):\n')

    for i, b in enumerate(blocks, 1):
        passage = ' '.join(ref_words[b.a : b.a + b.size])
        print(f'[{i}] ({b.size} words)')
        print(f'    {passage}\n')


def files_compare(reference_path, candidates_folder):
    if not os.path.isfile(reference_path):
        print(f'Reference file not found: {reference_path}')
        return

    candidates = sorted(glob.glob(os.path.join(candidates_folder, '**', '*.pdf'), recursive=True))
    # don't compare the reference against itself if it's sitting in the same folder tree
    candidates = [c for c in candidates if os.path.abspath(c) != os.path.abspath(reference_path)]

    if not candidates:
        print(f'No other PDFs found in {candidates_folder}')
        return

    print(f'Reference: {reference_path}')
    print(f'Comparing against {len(candidates)} file(s)...\n')

    ref_text = extract_text(reference_path)
    if not ref_text.strip():
        print('Warning: no extractable text found in reference PDF (might be scanned/image-based).')

    texts = [ref_text] + [extract_text(c) for c in candidates]

    vec = TfidfVectorizer(stop_words='english').fit_transform(texts)
    sims = cosine_similarity(vec[0:1], vec[1:]).flatten()

    results = sorted(zip(candidates, sims), key=lambda x: x[1], reverse=True)

    print(f'{"Similarity":>10}   File')
    print('-' * 50)
    for path, score in results:
        print(f'{score * 100:9.2f}%   {path}')

    print('\nNote: scores above ~70-80% usually indicate substantial text overlap;')
    print('this measures textual similarity, not proof of copying.')


files_compare(reference_path=os.path.join(os.path.expanduser('~'), 'Downloads', 'Reference.pdf'), candidates_folder=os.path.join(os.path.expanduser('~'), 'Downloads', 'Files'))

# overlap_display(reference_path=os.path.join(os.path.expanduser("~"), "Downloads", "Reference.pdf"), candidate_path=os.path.join(os.path.expanduser("~"), "Downloads", "Reference.pdf"), min_words=8)
