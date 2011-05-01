# parameters: dollar_table

create trigger trig_dollar_table after insert on dollar_table for each row
begin
	if (IS_NOT_NULL(new.amount) AND new.amount <> 'breast') then
		set @val = new.amount;
		if (new.amount > 9) then
			set @val = new.amount / 29.5735296;
		end if;
		update dollar_table set `amount_oz`=@val;
	end if;
end;
