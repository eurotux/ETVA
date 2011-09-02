#!/bin/bash
# random password generator by typedeaF
# This program has no "real" value other than introducing some bash concepts and putting them to use.
# to strip the comments: cat this.file | grep -v '^# '


# Sets the maximum size of the password the script will generate
MAXSIZE=8

# Holds valid password characters. I choose alpha-numeric + the shift-number keyboard keys
# I put escape chars on all the non alpha-numeric characters just for precaution
array1=(
q w e r t y u i o p a s d f g h j k l z x c v b n m Q W E R T Y U I O P A S D
F G H J K L Z X C V B N M 1 2 3 4 5 6 7 8 9 0 ! # $ % ^ & * ( )
)

# Used in conjunction with modulus to keep random numbers in range of the array size
MODNUM=${#array1[*]}

# Keeps track of the number characters in the password we have generated
pwd_len=0

# Bash's command substitution syntax to store the results of the tput command
term_clear=$(tput clear)

# Stores the number of lines or rows on the terminal display
max_lines=$(tput lines)

# Stores the number of columns on the terminal display
max_cols=$(tput cols)

# Finds the appropriate spot to indent for horizontally centered output
indent=$(( ((max_cols / 2)) - ((MAXSIZE / 2)) ))

# Finds the vertical center of the terminal.
line_num=$(( max_lines / 2 ))

# Clear the screen
#echo $term_clear

# The outer while loop starts at 0 and loops till MAXSIZE, creating a passwd char each iteration.
# The shells $RANDOM variable creates a semi-random unsigned number. This is our entropy. =x
# x simply holds some random unsigned int that will be used to make the character scramble.
# 500 was choosen for speed and nothing else. Leave out the mod 500 if you want or change it.
# The inner loop displays the password characters. Tput keeps the cursor in the proper position.
# Mod MODNUM keeps the random number inside the size of the array so it doesnt over index.
while [ $pwd_len -lt $MAXSIZE ]
do
    index=$(($RANDOM%$MODNUM))
  
    password="${password}${array1[$index]}"	
  ((pwd_len++))
done
# Place the cursor at the bottom of the screen --where is usually at.
# tput cup $max_lines 0
echo $password

exit 0 
