#TEXINPUTS := .:$(CURDIR)/include:${TEXINPUTS}
#export TEXINPUTS

MAIN = requisitos
RUBBER_OPTS = -d

all: *.tex
	@if [ -x /usr/bin/rubber ]; then rubber $(RUBBER_OPTS) $(MAIN); else pdflatex $(MAIN) && pdflatex $(MAIN); fi

clean:
	@rm -f *.aux *.log *.toc *.lof *.out

dist-clean: clean
	@rm -f $(MAIN).pdf

TEXFILES := $(shell find -type f -iname \*.tex  ! -wholename './.*')
spell:
	@for i in $(TEXFILES); do \
		aspell -c --lang=pt -t $$i; \
	done

