## Speech-to-Text
# Last update: 2024-10-12


"""About: Speech-to-Text. It requires the FFmpeg (https://ffmpeg.org/download.html)
Installation
Windows: winget install ffmpeg
Mac: brew install ffmpeg"""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import os
import speech_recognition as sr
from pydub import AudioSegment
from datetime import datetime


###########
# Functions
###########


def speech_to_text(*, mp3_path, output_directory, language):
    # Load your MP3 file
    audio = AudioSegment.from_mp3(file=mp3_path)

    # Export the MP3 file as a WAV file
    temp_wav_path = os.path.join(output_directory, 'temp.wav')
    audio.export(out_f=temp_wav_path, format='wav')

    # Initialize recognizer
    recognizer = sr.Recognizer()

    # Load the WAV file
    with sr.AudioFile(filename_or_fileobject=temp_wav_path) as source:
        audio_data = recognizer.record(source=source)

    # Recognize the speech
    try:
        text = recognizer.recognize_google(audio_data=audio_data, language=language)
        output_file_path = os.path.join(output_directory, f'recognized_text_{datetime.now().strftime("%Y%m%d_%H%M%S")}.txt')
        with open(file=output_file_path, mode='w', encoding='utf-8') as file_out:
            file_out.write(text)
        print(f'Text saved to {output_file_path}')

    except sr.RequestError:
        print('API could not be reached')
    except sr.UnknownValueError:
        print('Could not understand the audio')

    # Delete the temporary WAV file if it exists
    if os.path.exists(temp_wav_path):
        os.remove(path=temp_wav_path)


################
# Speech-to-Text
################

speech_to_text(mp3_path=os.path.join(os.path.expanduser('~'), 'Downloads', 'input.mp3'), output_directory=os.path.join(os.path.expanduser('~'), 'Downloads'), language='de-DE')
